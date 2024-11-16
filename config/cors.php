<?php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', '*','broadcasting/auth','get-cities', 'get-districts/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['http://localhost:8000', 'http://localhost:3000'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
    'credentials' => true,

];  