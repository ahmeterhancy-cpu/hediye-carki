<?php

namespace App\Core;

class Csrf
{
    private const TOKEN_KEY = '_csrf_token';
    private const TOKEN_LIFETIME = 3600;

    public static function token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (
            empty($_SESSION[self::TOKEN_KEY]) ||
            (time() - ($_SESSION['_csrf_ts'] ?? 0)) > self::TOKEN_LIFETIME
        ) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
            $_SESSION['_csrf_ts'] = time();
        }

        return $_SESSION[self::TOKEN_KEY];
    }

    public static function verify(string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $stored = $_SESSION[self::TOKEN_KEY] ?? '';
        return $stored !== '' && hash_equals($stored, $token);
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function check(): void
    {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!self::verify($token)) {
            // /spin/* endpoint'lerinde kullanıcıyı baştan başlat
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            if (str_starts_with($uri, '/spin/')) {
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_unset();
                    session_destroy();
                }
                Response::redirect('/');
            }

            // Admin/staff'ta açıklayıcı mesaj + login'e yönlendirme
            if (str_starts_with($uri, '/admin/')) Response::redirect('/admin/login');
            if (str_starts_with($uri, '/staff/')) Response::redirect('/staff/login');

            Response::abort(403, 'Oturum süresi dolmuş. Lütfen sayfayı yenileyin.');
        }
    }
}
