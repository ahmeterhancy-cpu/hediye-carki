<?php

namespace App\Core;

class Auth
{
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;

        $cfg = require __DIR__ . '/../../config/app.php';
        session_name($cfg['session']['name']);

        session_set_cookie_params([
            'lifetime' => $cfg['session']['lifetime'],
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    // --- Admin ---

    public static function adminCheck(): void
    {
        self::startSession();
        if (empty($_SESSION['admin_id'])) {
            Response::redirect('/admin/login');
        }
    }

    public static function adminLogin(int $id, string $name): void
    {
        self::startSession();
        session_regenerate_id(true);
        $_SESSION['admin_id']   = $id;
        $_SESSION['admin_name'] = $name;
    }

    public static function adminLogout(): void
    {
        self::startSession();
        session_unset();
        session_destroy();
    }

    public static function adminId(): ?int
    {
        return isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
    }

    public static function adminName(): string
    {
        return $_SESSION['admin_name'] ?? '';
    }

    // --- Staff ---

    public static function staffCheck(): void
    {
        self::startSession();
        if (empty($_SESSION['staff_id'])) {
            Response::redirect('/staff/login');
        }
    }

    public static function staffLogin(int $id, string $name): void
    {
        self::startSession();
        session_regenerate_id(true);
        $_SESSION['staff_id']   = $id;
        $_SESSION['staff_name'] = $name;
    }

    public static function staffLogout(): void
    {
        self::startSession();
        unset($_SESSION['staff_id'], $_SESSION['staff_name']);
        session_regenerate_id(true);
    }

    public static function staffId(): ?int
    {
        return isset($_SESSION['staff_id']) ? (int)$_SESSION['staff_id'] : null;
    }

    public static function staffName(): string
    {
        return $_SESSION['staff_name'] ?? '';
    }

    // --- Customer (kiosk session) ---

    public static function setSpinCode(array $codeRow): void
    {
        self::startSession();
        $_SESSION['spin_code'] = $codeRow;
    }

    public static function getSpinCode(): ?array
    {
        self::startSession();
        return $_SESSION['spin_code'] ?? null;
    }

    public static function clearSpinCode(): void
    {
        self::startSession();
        unset($_SESSION['spin_code'], $_SESSION['spin_registration']);
    }

    public static function setRegistration(array $data): void
    {
        self::startSession();
        $_SESSION['spin_registration'] = $data;
    }

    public static function getRegistration(): ?array
    {
        self::startSession();
        return $_SESSION['spin_registration'] ?? null;
    }

    public static function clearCustomer(): void
    {
        self::startSession();
        session_unset();
        session_destroy();
    }
}
