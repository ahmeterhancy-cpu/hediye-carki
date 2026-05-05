<?php

namespace App\Core;

/**
 * Basit .env okuyucu — parse_ini_file'in özel karakter sorunu olmadan çalışır.
 * Şifrede !, &, (, ), ?, {, }, =, ;, # gibi karakterler olsa da düzgün okur.
 */
class EnvLoader
{
    private static ?array $cache = null;

    public static function load(string $path): array
    {
        if (self::$cache !== null) return self::$cache;

        $env = [];
        if (!file_exists($path)) {
            self::$cache = $env;
            return $env;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, ';')) continue;

            $eqPos = strpos($line, '=');
            if ($eqPos === false) continue;

            $key   = trim(substr($line, 0, $eqPos));
            $value = trim(substr($line, $eqPos + 1));

            // Inline comment temizle (sadece quote dışında)
            if ($value !== '' && $value[0] !== '"' && $value[0] !== "'") {
                if (($hashPos = strpos($value, '#')) !== false) {
                    $value = trim(substr($value, 0, $hashPos));
                }
            }

            // Quote'ları kaldır
            $len = strlen($value);
            if ($len >= 2) {
                $first = $value[0];
                $last  = $value[$len - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            $env[$key] = $value;
        }

        self::$cache = $env;
        return $env;
    }
}
