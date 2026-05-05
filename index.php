<?php

declare(strict_types=1);

define('BASE_PATH', __DIR__);

require BASE_PATH . '/vendor/autoload.php';

use App\Core\Auth;
use App\Core\Router;
use App\Controllers\AdminController;
use App\Controllers\StaffController;

Auth::startSession();

$cfg = require BASE_PATH . '/config/app.php';
if ($cfg['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

$router = new Router();

// ── Ana sayfa: görevliye yönlendir ─────────────────────────────────
$router->get('/', function () {
    \App\Core\Response::redirect(\App\Core\Auth::staffId() ? '/staff' : '/staff/login');
});

// ── Admin ────────────────────────────────────────────────────────────
$router->get( '/admin/login',                          [AdminController::class, 'loginForm']);
$router->post('/admin/login',                          [AdminController::class, 'loginPost']);
$router->post('/admin/logout',                         [AdminController::class, 'logout']);

$router->get( '/admin',                                [AdminController::class, 'dashboard']);
$router->get( '/admin/prizes',                         [AdminController::class, 'prizes']);
$router->post('/admin/prizes',                         [AdminController::class, 'prizeCreate']);
$router->post('/admin/prizes/reorder',                 [AdminController::class, 'prizeReorder']);
$router->post('/admin/prizes/{id}/delete',             [AdminController::class, 'prizeDelete']);
$router->post('/admin/prizes/{id}',                    [AdminController::class, 'prizeUpdate']);

$router->get( '/admin/settings',                       [AdminController::class, 'settings']);
$router->post('/admin/settings',                       [AdminController::class, 'settingsPost']);

$router->get( '/admin/stock',                          [AdminController::class, 'stock']);
$router->post('/admin/stock/{prize_id}',               [AdminController::class, 'stockUpdate']);

$router->get( '/admin/participants',                   [AdminController::class, 'participants']);
$router->get( '/admin/participants/export',            [AdminController::class, 'participantsExport']);
$router->post('/admin/participants/clear',             [AdminController::class, 'participantsClear']);
$router->get( '/admin/reports',                        [AdminController::class, 'reports']);

$router->get( '/admin/staff-users',                    [AdminController::class, 'staffUsers']);
$router->post('/admin/staff-users',                    [AdminController::class, 'staffCreate']);
$router->get( '/admin/staff-users/generate-pin',       [AdminController::class, 'staffGeneratePin']);
$router->post('/admin/staff-users/{id}/reset-pin',     [AdminController::class, 'staffResetPin']);
$router->post('/admin/staff-users/{id}/toggle',        [AdminController::class, 'staffToggle']);
$router->post('/admin/staff-users/{id}/delete',        [AdminController::class, 'staffDelete']);

$router->get( '/admin/admins',                         [AdminController::class, 'admins']);
$router->post('/admin/admins',                         [AdminController::class, 'adminCreate']);
$router->post('/admin/admins/{id}',                    [AdminController::class, 'adminUpdate']);
$router->post('/admin/admins/{id}/password',           [AdminController::class, 'adminPasswordChange']);
$router->post('/admin/admins/{id}/toggle',             [AdminController::class, 'adminToggle']);
$router->post('/admin/admins/{id}/delete',             [AdminController::class, 'adminDelete']);

// ── Görevli (tek-cihaz akışı) ───────────────────────────────────────
$router->get( '/staff/login',                          [StaffController::class, 'loginForm']);
$router->post('/staff/login',                          [StaffController::class, 'loginPost']);
$router->post('/staff/logout',                         [StaffController::class, 'logout']);

$router->get( '/staff',                                [StaffController::class, 'customerForm']);
$router->post('/staff/customer',                       [StaffController::class, 'customerSubmit']);
$router->get( '/staff/spin',                           [StaffController::class, 'spin']);
$router->post('/staff/spin/execute',                   [StaffController::class, 'spinExecute']);
$router->get( '/staff/win/{participant_id}',           [StaffController::class, 'win']);
$router->post('/staff/new',                            [StaffController::class, 'newCustomer']);

$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$router->dispatch($method, $uri);
