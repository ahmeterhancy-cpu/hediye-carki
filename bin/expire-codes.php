#!/usr/bin/env php
<?php

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

use App\Services\CodeService;

$expired = (new CodeService())->expireStale();
echo date('Y-m-d H:i:s') . " — {$expired} kod expired olarak işaretlendi.\n";
