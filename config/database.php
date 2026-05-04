<?php

$env = parse_ini_file(__DIR__ . '/../.env') ?: [];

return [
    'host'    => $env['DB_HOST'] ?? '127.0.0.1',
    'port'    => $env['DB_PORT'] ?? '3306',
    'name'    => $env['DB_NAME'] ?? 'hediye_carki',
    'user'    => $env['DB_USER'] ?? 'root',
    'pass'    => $env['DB_PASS'] ?? '',
    'charset' => 'utf8mb4',
];
