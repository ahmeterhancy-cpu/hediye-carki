<?php

require_once __DIR__ . '/../app/Core/EnvLoader.php';

$env = \App\Core\EnvLoader::load(__DIR__ . '/../.env');

return [
    'url'     => $env['APP_URL']   ?? 'http://localhost',
    'env'     => $env['APP_ENV']   ?? 'production',
    'debug'   => ($env['APP_DEBUG'] ?? 'false') === 'true',
    'key'     => $env['APP_KEY']   ?? '',
    'session' => [
        'name'     => $env['SESSION_NAME']     ?? 'hcarki_sess',
        'lifetime' => (int)($env['SESSION_LIFETIME'] ?? 7200),
    ],
];
