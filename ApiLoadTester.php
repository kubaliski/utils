<?php
/**
 * API Load Tester
 *
 * Este script permite realizar pruebas de carga sobre endpoints de API REST.
 * Soporta autenticación mediante tokens Bearer y manejo de múltiples peticiones
 * concurrentes utilizando promesas asíncronas.
 *
 * Características principales:
 * - Autenticación mediante Bearer token
 * - Peticiones concurrentes configurables
 * - Control de delay entre batches de peticiones
 * - Manejo automático de rate limiting (código 429)
 * - Reportes detallados de resultados
 *
 * @author Kubaliski
 */

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

/**
 * Clase principal para realizar pruebas de carga sobre APIs
 */
class ApiLoadTester
{
    /** @var Client Instancia del cliente HTTP Guzzle */
    private $client;

    /** @var string URL base de la API */
    private $baseUrl;

    /** @var int Número de peticiones concurrentes */
    private $concurrency;

    /** @var int Número total de peticiones a realizar */
    private $totalRequests;

    /** @var int Contador de peticiones exitosas */
    private $successCount = 0;

    /** @var int Contador de peticiones fallidas */
    private $failureCount = 0;

    /** @var array Almacena los tiempos de respuesta de cada petición */
    private $timings = [];

    /** @var string|null Token de autenticación */
    private $token;

    /** @var int Delay en milisegundos entre batches de peticiones */
    private $delayBetweenRequests;

    /**
     * Constructor de la clase ApiLoadTester
     *
     * @param string $baseUrl URL base de la API
     * @param string|null $token Token de autenticación (opcional)
     * @param int $concurrency Número de peticiones concurrentes
     * @param int $totalRequests Número total de peticiones a realizar
     * @param int $delayBetweenRequests Delay en milisegundos entre batches
     */
    public function __construct(
        string $baseUrl,
        string $token = null,
        int $concurrency = 5,
        int $totalRequests = 50,
        int $delayBetweenRequests = 1000
    ) {
        $this->baseUrl = $baseUrl;
        $this->token = $token;
        $this->concurrency = $concurrency;
        $this->totalRequests = $totalRequests;
        $this->delayBetweenRequests = $delayBetweenRequests;

        // Configuración de headers por defecto
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];

        // Añadir token de autenticación si está presente
        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        // Inicializar cliente HTTP con la configuración base
        $this->client = new Client([
            'timeout' => 30,         // Timeout en segundos
            'verify' => false,       // Deshabilitar verificación SSL
            'headers' => $headers,
            'http_errors' => false   // No lanzar excepciones por errores HTTP
        ]);
    }

    /**
     * Realiza el login en la API y obtiene un token de autenticación
     *
     * @param string $loginEndpoint Endpoint de login
     * @param array $credentials Credenciales de acceso (email, password)
     * @return bool True si el login fue exitoso, False en caso contrario
     */
    public function loginAndGetToken($loginEndpoint, array $credentials)
    {
        try {
            $response = $this->client->post($this->baseUrl . $loginEndpoint, [
                'json' => $credentials
            ]);

            $data = json_decode($response->getBody(), true);
            $this->token = $data['token'] ?? $data['access_token'] ?? null;

            if ($this->token) {
                // Actualizar cliente con el nuevo token
                $this->client = new Client([
                    'timeout' => 30,
                    'verify' => false,
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $this->token
                    ],
                    'http_errors' => false
                ]);
                return true;
            }

            return false;
        } catch (RequestException $e) {
            echo "Error al obtener token: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Ejecuta la prueba de carga sobre un endpoint específico
     *
     * @param string $endpoint Endpoint a probar
     * @param string $method Método HTTP a utilizar (GET, POST, etc)
     * @param array $data Datos a enviar en caso de POST/PUT
     */
    public function runTest(string $endpoint, string $method = 'GET', array $data = [])
    {
        if (!$this->token) {
            echo "Advertencia: Ejecutando prueba sin token de autenticación\n";
        }

        $requestCount = 0;
        $startTime = microtime(true);

        while ($requestCount < $this->totalRequests) {
            $promises = [];

            // Crear un batch de promesas según la concurrencia configurada
            for ($i = 0; $i < $this->concurrency && $requestCount < $this->totalRequests; $i++) {
                $promises[] = $this->client->requestAsync(
                    $method,
                    $this->baseUrl . $endpoint,
                    $method === 'GET' ? [] : ['json' => $data]
                );
                $requestCount++;
            }

            // Esperar a que se completen todas las promesas del batch actual
            $results = \GuzzleHttp\Promise\Utils::settle($promises)->wait();

            // Procesar resultados del batch
            foreach ($results as $index => $result) {
                if ($result['state'] === 'fulfilled') {
                    $response = $result['value'];
                    $statusCode = $response->getStatusCode();

                    if ($statusCode === 200) {
                        $this->successCount++;
                        $this->timings[] = $response->getHeaderLine('X-Runtime') ?? 0;
                    } else {
                        $this->failureCount++;
                        echo "Petición fallida con status: $statusCode\n";
                        // Manejo de rate limiting
                        if ($statusCode === 429) {
                            $this->delayBetweenRequests *= 1.5;
                            echo "Aumentando delay entre peticiones a " .
                                 ($this->delayBetweenRequests/1000) . " segundos\n";
                        }
                    }
                } else {
                    $this->failureCount++;
                    echo "Error en petición: " . $result['reason']->getMessage() . "\n";
                }
            }

            // Aplicar delay entre batches
            if ($requestCount < $this->totalRequests) {
                usleep($this->delayBetweenRequests * 1000);
            }
        }

        $endTime = microtime(true);
        $this->printResults($startTime, $endTime);
    }

    /**
     * Imprime los resultados de la prueba de carga
     *
     * @param float $startTime Tiempo de inicio de la prueba
     * @param float $endTime Tiempo de finalización de la prueba
     */
    private function printResults($startTime, $endTime)
    {
        $totalTime = $endTime - $startTime;
        $averageTime = !empty($this->timings) ? array_sum($this->timings) / count($this->timings) : 0;

        echo "\n=== Resultados de la Prueba de Carga ===\n";
        echo "Total de peticiones: " . $this->totalRequests . "\n";
        echo "Concurrencia: " . $this->concurrency . "\n";
        echo "Delay entre peticiones: " . ($this->delayBetweenRequests/1000) . " segundos\n";
        echo "Peticiones exitosas: " . $this->successCount . "\n";
        echo "Peticiones fallidas: " . $this->failureCount . "\n";
        echo "Tiempo total de ejecución: " . round($totalTime, 2) . " segundos\n";
        echo "Tiempo promedio por petición: " . round($averageTime * 1000, 2) . " ms\n";
        echo "Peticiones por segundo: " . round($this->totalRequests / $totalTime, 2) . "\n";
    }
}

// Cargar archivo de configuración
$config = require 'config.php';

// Inicializar el tester con la configuración cargada
$tester = new ApiLoadTester(
    $config['api']['base_url'],
    null,
    $config['test_settings']['concurrency'],
    $config['test_settings']['total_requests'],
    $config['test_settings']['delay_between_requests']
);

// Realizar login y obtener token
$loginSuccess = $tester->loginAndGetToken(
    $config['api']['endpoints']['login'],
    [
        'email' => $config['auth']['email'],
        'password' => $config['auth']['password']
    ]
);

// Ejecutar la prueba si el login fue exitoso
if ($loginSuccess) {
    $tester->runTest($config['api']['endpoints']['endpoint'], 'GET');
}