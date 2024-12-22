<?php

return [
    'api' => [
        'base_url' => 'https://example-api.com',
        'endpoints' => [
            'login' => '/api/login',
            'endpoint' => '/api/your-endpoint'
        ]
    ],
    'auth' => [
        'email' => 'example@email.com',
        'password' => 'your-password'
    ],
    'test_settings' => [
        'concurrency' => 5,
        'total_requests' => 100,
        'delay_between_requests' => 1000
    ]
];