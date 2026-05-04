<?php

$env = parse_ini_file(__DIR__ . '/../.env') ?: [];

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
