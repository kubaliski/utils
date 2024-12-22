# API Load Tester

API Load Tester es una herramienta diseñada para realizar pruebas de carga sobre APIs REST. Permite ejecutar múltiples peticiones concurrentes, manejar autenticación mediante tokens y proporciona métricas detalladas sobre el rendimiento.

## Características

- Soporte para autenticación mediante Bearer token
- Peticiones concurrentes configurables
- Control de delay entre batches de peticiones
- Manejo automático de rate limiting (código 429)
- Reportes detallados de resultados
- Configuración flexible mediante archivo de configuración

## Requisitos

- PHP 7.4 o superior
- Composer
- Extensión PHP cURL habilitada

## Instalación

1. Clona el repositorio:

```bash
git clone [url-del-repositorio]
cd api-load-tester
```

2. Instala las dependencias mediante Composer:

```bash
composer install
```

3. Crea tu archivo de configuración:

```bash
cp config.example.php config.php
```

## Configuración

El archivo `config.php` contiene toda la configuración necesaria para ejecutar las pruebas. Debes modificar este archivo con tus propios valores:

```php
return [
    'api' => [
        'base_url' => 'https://tu-api.com',  // URL base de tu API
        'endpoints' => [
            'login' => '/api/login',         // Endpoint de login
            'endpoint' => '/api/tu-endpoint' // Endpoint a probar
        ]
    ],
    'auth' => [
        'email' => 'tu-email@ejemplo.com',   // Email para autenticación
        'password' => 'tu-password'          // Contraseña
    ],
    'test_settings' => [
        'concurrency' => 5,                  // Número de peticiones concurrentes
        'total_requests' => 100,             // Total de peticiones a realizar
        'delay_between_requests' => 1000     // Delay en milisegundos entre batches
    ]
];
```

### Parámetros de Configuración

- **base_url**: URL base de la API a probar
- **endpoints**:
  - **login**: Ruta del endpoint de login
  - **endpoint**: Ruta del endpoint a probar
- **auth**: Credenciales de autenticación
- **test_settings**:
  - **concurrency**: Número de peticiones simultáneas
  - **total_requests**: Número total de peticiones a realizar
  - **delay_between_requests**: Tiempo de espera en milisegundos entre batches de peticiones

## Uso

1. Asegúrate de tener tu `config.php` configurado correctamente.

2. Ejecuta el script:

```bash
php api_load_tester.php
```

3. El script realizará:
   - Login en la API usando las credenciales proporcionadas
   - Obtendrá un token de autenticación
   - Ejecutará las pruebas de carga sobre el endpoint especificado
   - Mostrará un reporte detallado de resultados

## Resultados

El script mostrará un reporte con la siguiente información:

```
=== Resultados de la Prueba de Carga ===
Total de peticiones: [número total de peticiones]
Concurrencia: [número de peticiones concurrentes]
Delay entre peticiones: [delay en segundos]
Peticiones exitosas: [número de peticiones exitosas]
Peticiones fallidas: [número de peticiones fallidas]
Tiempo total de ejecución: [tiempo total en segundos]
Tiempo promedio por petición: [tiempo promedio en ms]
Peticiones por segundo: [número de peticiones por segundo]
```

## Manejo de Rate Limiting

El script incluye manejo automático de rate limiting:

- Si recibe un código de respuesta 429 (Too Many Requests)
- Automáticamente incrementa el delay entre peticiones en un 50%
- Muestra un mensaje informando del nuevo delay

## Consideraciones de Seguridad

- El archivo `config.php` está incluido en `.gitignore` para evitar subir credenciales al repositorio
- Se recomienda usar credenciales de prueba, no de producción
- Ajusta los valores de concurrencia y total de peticiones según la capacidad de tu API

## Personalización

Puedes modificar los siguientes aspectos del script:

- Timeout de las peticiones (por defecto 30 segundos)
- Headers adicionales en las peticiones
- Método HTTP (GET, POST, etc.)
- Formato de los datos enviados

## Solución de Problemas

1. **Error de autenticación**:

   - Verifica las credenciales en config.php
   - Asegúrate de que el endpoint de login sea correcto

2. **Timeout en peticiones**:

   - Aumenta el valor de timeout en la configuración del cliente
   - Reduce la concurrencia

3. **Errores SSL**:
   - El script tiene deshabilitada la verificación SSL por defecto
   - Para habilitar la verificación, modifica 'verify' => true en la configuración del cliente

## Limitaciones

- Solo soporta autenticación mediante Bearer token
- No realiza validación del contenido de las respuestas
- No soporta subida de archivos

## Contribuir

Si deseas contribuir al proyecto:

1. Haz fork del repositorio
2. Crea una rama para tu feature
3. Realiza tus cambios
4. Envía un pull request
