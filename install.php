<?php
/**
 * Tek kullanımlık kurulum scripti.
 * Kullanım: https://siteniz.com/install.php?secret=INSTALL_SECRET
 * Kurulum bittikten sonra bu dosyayı silin veya yeniden adlandırın.
 */

define('BASE_PATH', __DIR__);
define('MAX_AGE_SECS', 3600); // 1 saatte script kendini devre dışı bırakır

// ── Temel güvenlik ─────────────────────────────────────────────────────────
$envFile = BASE_PATH . '/.env';
if (!file_exists($envFile)) {
    die('<pre style="color:red">HATA: .env dosyası bulunamadı. Önce .env dosyasını oluşturun.</pre>');
}

// .env'den INSTALL_SECRET oku
$envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$envVars  = [];
foreach ($envLines as $line) {
    if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
    [$k, $v] = explode('=', $line, 2);
    $envVars[trim($k)] = trim($v);
}

$secret = $envVars['INSTALL_SECRET'] ?? null;
if (!$secret || strlen($secret) < 12) {
    die('<pre style="color:red">HATA: .env dosyasına INSTALL_SECRET=... ekleyin (en az 12 karakter).</pre>');
}

$given = $_GET['secret'] ?? '';
if (!hash_equals($secret, $given)) {
    http_response_code(403);
    die('<pre style="color:red">403 Yasak. ?secret= parametresi eksik veya hatalı.</pre>');
}

// Zaten kurulmuş mu?
$doneFile = BASE_PATH . '/storage/install.done';
if (file_exists($doneFile)) {
    die('<pre style="color:green">Kurulum zaten tamamlanmış. Bu dosyayı silin: public/install.php</pre>');
}

// ── Autoload ───────────────────────────────────────────────────────────────
require BASE_PATH . '/vendor/autoload.php';
use App\Core\Database;

// ── Stil ───────────────────────────────────────────────────────────────────
?><!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Hediye Çarkı — Kurulum</title>
<style>
  body { font-family: monospace; background: #0f172a; color: #e2e8f0; padding: 2rem; }
  h1   { color: #fbbf24; margin-bottom: 1.5rem; }
  .ok  { color: #4ade80; }
  .err { color: #f87171; }
  .box { background: #1e293b; border-radius: 8px; padding: 1.5rem; max-width: 700px; }
  .done{ background: #14532d; border: 1px solid #4ade80; border-radius: 8px; padding: 1rem; margin-top: 1.5rem; }
  .warn{ background: #78350f; border: 1px solid #fbbf24; border-radius: 8px; padding: 1rem; margin-top: 1rem; }
</style>
</head>
<body>
<div class="box">
<h1>🎡 Hediye Çarkı — Kurulum</h1>
<?php

$ok    = true;
$steps = [];

// ── 1. DB bağlantısı ───────────────────────────────────────────────────────
echo "<p>Veritabanına bağlanılıyor...</p>";
try {
    $pdo = Database::pdo();
    echo '<p class="ok">✓ Veritabanı bağlantısı başarılı.</p>';
} catch (\Throwable $e) {
    echo '<p class="err">✗ DB bağlantı hatası: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<div class="warn">⚠ .env dosyasındaki DB_HOST, DB_NAME, DB_USER, DB_PASS değerlerini kontrol edin.</div>';
    echo '</div></body></html>';
    exit;
}

// ── 2. Migration'lar ──────────────────────────────────────────────────────
echo "<p>Tablolar oluşturuluyor...</p><ul>";
$migrations = glob(BASE_PATH . '/database/migrations/*.sql');
sort($migrations);
foreach ($migrations as $file) {
    $name = basename($file);
    try {
        $sql = file_get_contents($file);
        $pdo->exec($sql);
        echo '<li class="ok">✓ ' . htmlspecialchars($name) . '</li>';
    } catch (\PDOException $e) {
        $msg = $e->getMessage();
        // "already exists" hataları uyarı, diğerleri hata
        if (stripos($msg, 'already exists') !== false || stripos($msg, 'duplicate') !== false) {
            echo '<li style="color:#93c5fd">~ ' . htmlspecialchars($name) . ' (zaten mevcut, atlandı)</li>';
        } else {
            echo '<li class="err">✗ ' . htmlspecialchars($name) . ': ' . htmlspecialchars($msg) . '</li>';
            $ok = false;
        }
    }
}
echo "</ul>";

// ── 3. Seed ───────────────────────────────────────────────────────────────
if ($ok) {
    echo "<p>Başlangıç verileri yükleniyor...</p>";
    try {
        $seedSql = file_get_contents(BASE_PATH . '/database/seed.sql');
        // Yorum satırlarını çıkar, bloklara ayır
        $seedSql = preg_replace('/--[^\n]*/', '', $seedSql);
        $stmts   = array_filter(array_map('trim', explode(';', $seedSql)));
        foreach ($stmts as $stmt) {
            if ($stmt) $pdo->exec($stmt);
        }
        echo '<p class="ok">✓ Başlangıç verileri yüklendi (admin@local / admin123).</p>';
    } catch (\PDOException $e) {
        $msg = $e->getMessage();
        if (stripos($msg, 'duplicate') !== false || stripos($msg, 'Duplicate') !== false) {
            echo '<p style="color:#93c5fd">~ Seed verileri zaten mevcut, atlandı.</p>';
        } else {
            echo '<p class="err">✗ Seed hatası: ' . htmlspecialchars($msg) . '</p>';
        }
    }
}

// ── 4. Storage klasörleri ─────────────────────────────────────────────────
$dirs = [
    BASE_PATH . '/storage/logs',
    BASE_PATH . '/storage/exports',
    BASE_PATH . '/storage/uploads',
    BASE_PATH . '/uploads',
];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true)
            ? print('<p class="ok">✓ Klasör oluşturuldu: ' . basename(dirname($dir)) . '/' . basename($dir) . '</p>')
            : print('<p class="err">✗ Klasör oluşturulamadı: ' . htmlspecialchars($dir) . '</p>');
    }
}

// ── 5. Tamamla ────────────────────────────────────────────────────────────
if ($ok) {
    file_put_contents($doneFile, date('Y-m-d H:i:s'));
    echo '
<div class="done">
  <strong>🎉 Kurulum tamamlandı!</strong><br><br>
  <strong>Yapmanız gerekenler:</strong><br>
  1. Bu dosyayı silin: <code>public/install.php</code><br>
  2. Admin girişi: <a href="/admin/login" style="color:#4ade80">/admin/login</a> → <code>admin@local</code> / <code>admin123</code><br>
  3. <strong>Admin şifresini hemen değiştirin!</strong> (DB\'den hash ile)<br>
  4. .env dosyasından <code>INSTALL_SECRET</code> satırını silin.
</div>';
} else {
    echo '<div class="warn">⚠ Kurulum hatalarla tamamlandı. Yukarıdaki hataları düzeltin ve tekrar deneyin.</div>';
}
?>
</div>
</body>
</html>
