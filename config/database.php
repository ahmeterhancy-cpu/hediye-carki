<?php

require_once __DIR__ . '/../app/Core/EnvLoader.php';

$env = \App\Core\EnvLoader::load(__DIR__ . '/../.env');

return [
    'host'    => $env['DB_HOST'] ?? '127.0.0.1',
    'port'    => $env['DB_PORT'] ?? '3306',
    'name'    => $env['DB_NAME'] ?? 'hediye_carki',
    'user'    => $env['DB_USER'] ?? 'root',
    'pass'    => $env['DB_PASS'] ?? '',
    'charset' => 'utf8mb4',
];
