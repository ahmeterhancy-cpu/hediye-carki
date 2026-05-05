<?php
/**
 * Self-updater — GitHub'tan en son sürümü çeker, dosyaları yeniler.
 * Kullanım: https://siteniz.com/update.php?secret=INSTALL_SECRET
 *
 * .env, storage/*, media/*, vendor/*  korunur.
 */

declare(strict_types=1);

set_time_limit(120);
@ini_set('memory_limit', '256M');

define('BASE_PATH', __DIR__);
define('REPO_ZIP', 'https://github.com/ahmeterhancy-cpu/hediye-carki/archive/refs/heads/master.zip');

// ── .env'den secret oku ─────────────────────────────────────────────
$envFile = BASE_PATH . '/.env';
if (!file_exists($envFile)) {
    die('<pre style="color:red">HATA: .env dosyası yok.</pre>');
}

$envVars = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
    [$k, $v] = explode('=', $line, 2);
    $v = trim($v);
    if (strlen($v) >= 2 && (($v[0] === '"' && $v[-1] === '"') || ($v[0] === "'" && $v[-1] === "'"))) {
        $v = substr($v, 1, -1);
    }
    $envVars[trim($k)] = $v;
}

$secret = $envVars['INSTALL_SECRET'] ?? '';
if (!$secret || strlen($secret) < 12) {
    die('<pre style="color:red">.env dosyasına INSTALL_SECRET=... ekleyin (en az 12 karakter).</pre>');
}

if (!hash_equals($secret, $_GET['secret'] ?? '')) {
    http_response_code(403);
    die('<pre style="color:red">403 — secret hatalı.</pre>');
}

// ── HTML başlığı ────────────────────────────────────────────────────
?><!DOCTYPE html>
<html lang="tr"><head><meta charset="UTF-8"><title>Güncelleme</title>
<style>
  body { font-family: monospace; background:#0f172a; color:#e2e8f0; padding:2rem; }
  h1 { color:#fbbf24; }
  .ok { color:#4ade80; } .err { color:#f87171; } .warn { color:#fbbf24; }
  .box { background:#1e293b; padding:1.5rem; border-radius:8px; max-width:900px; }
  ul { line-height:1.6; } li { margin:2px 0; }
</style></head><body><div class="box"><h1>🔄 Güncelleme</h1><?php

function out(string $msg, string $cls = ''): void {
    echo $cls ? "<p class=\"$cls\">$msg</p>" : "<p>$msg</p>";
    @ob_flush(); @flush();
}

// ── 1. ZIP indir ────────────────────────────────────────────────────
out('GitHub\'tan ZIP indiriliyor...');
$zipPath = BASE_PATH . '/storage/_update_' . bin2hex(random_bytes(4)) . '.zip';
@mkdir(dirname($zipPath), 0775, true);

function downloadZip(string $url, string $dest): array {
    // 1) cURL (en güvenilir)
    if (function_exists('curl_init')) {
        $fp = fopen($dest, 'w');
        if (!$fp) return [false, 'fopen başarısız'];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-SelfUpdate');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        $ok   = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);
        if ($ok && $code === 200 && filesize($dest) > 0) {
            return [true, 'curl'];
        }
        @unlink($dest);
        $curlMsg = "cURL: " . ($err ?: "HTTP {$code}");
    } else {
        $curlMsg = 'cURL yok';
    }

    // 2) file_get_contents fallback
    if (ini_get('allow_url_fopen')) {
        $ctx  = stream_context_create(['http' => ['timeout' => 90, 'user_agent' => 'PHP-SelfUpdate']]);
        $data = @file_get_contents($url, false, $ctx);
        if ($data && file_put_contents($dest, $data)) {
            return [true, 'fopen'];
        }
        $curlMsg .= ' / file_get_contents başarısız';
    } else {
        $curlMsg .= ' / allow_url_fopen kapalı';
    }

    return [false, $curlMsg];
}

[$ok, $msg] = downloadZip(REPO_ZIP, $zipPath);
if (!$ok) {
    out('✗ ZIP indirilemedi: ' . htmlspecialchars($msg), 'err');
    out('Hosting destekten cURL veya allow_url_fopen aktivasyonu isteyin.', 'warn');
    exit;
}
out('✓ ZIP indirildi (' . round(filesize($zipPath) / 1024) . ' KB, yöntem: ' . $msg . ')', 'ok');

// ── 2. Extract ──────────────────────────────────────────────────────
$tmpDir = BASE_PATH . '/storage/_update_extract_' . bin2hex(random_bytes(4));
mkdir($tmpDir, 0775, true);

$zip = new ZipArchive();
if ($zip->open($zipPath) !== true) {
    out('✗ ZIP açılamadı.', 'err');
    @unlink($zipPath);
    exit;
}
$zip->extractTo($tmpDir);
$zip->close();
@unlink($zipPath);
out('✓ ZIP çıkartıldı.', 'ok');

// ── 3. Kök dizini bul (hediye-carki-master/) ────────────────────────
$entries = array_diff(scandir($tmpDir), ['.', '..']);
$srcDir  = $tmpDir . '/' . reset($entries);
if (!is_dir($srcDir)) {
    out('✗ Kaynak klasör bulunamadı.', 'err');
    exit;
}

// ── 4. Dosyaları kopyala ────────────────────────────────────────────
// Kopyalanmayacak yollar (vendor dahil edilir — public_html self-contained olsun)
$skipPaths = [
    '.env',
    '.git',
    '.gitignore',
    '.gitattributes',
    '.cpanel.yml',
    'storage/logs',
    'storage/exports',
    'storage/uploads',
    'uploads',
    'media',
];

function shouldSkip(string $relPath, array $skip): bool {
    foreach ($skip as $s) {
        if ($relPath === $s) return true;
        if (str_starts_with($relPath . '/', $s . '/')) return true;
    }
    return false;
}

$copied = 0;
$skipped = 0;
$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($it as $item) {
    $rel = ltrim(str_replace('\\', '/', substr($item->getPathname(), strlen($srcDir))), '/');
    if (shouldSkip($rel, $skipPaths)) { $skipped++; continue; }

    $dest = BASE_PATH . '/' . $rel;

    if ($item->isDir()) {
        if (!is_dir($dest)) mkdir($dest, 0775, true);
    } else {
        $dir = dirname($dest);
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        copy($item->getPathname(), $dest);
        $copied++;
    }
}

out("✓ {$copied} dosya güncellendi, {$skipped} atlandı (.env, storage, vendor korunur).", 'ok');

// ── 5. Tmp temizlik ─────────────────────────────────────────────────
function rrmdir(string $dir): void {
    if (!is_dir($dir)) return;
    foreach (array_diff(scandir($dir), ['.', '..']) as $f) {
        $p = "$dir/$f";
        is_dir($p) && !is_link($p) ? rrmdir($p) : @unlink($p);
    }
    @rmdir($dir);
}
rrmdir($tmpDir);
out('✓ Geçici dosyalar silindi.', 'ok');

// ── 6. Yazılabilir klasörleri garanti et ────────────────────────────
$writableDirs = [
    BASE_PATH . '/media',
    BASE_PATH . '/storage',
    BASE_PATH . '/storage/logs',
    BASE_PATH . '/storage/exports',
    BASE_PATH . '/storage/uploads',
];
foreach ($writableDirs as $d) {
    // Dosya-klasör çakışması var mı?
    if (file_exists($d) && !is_dir($d)) {
        out('⚠ Dosya bulundu (klasör olması gerekiyordu): ' . htmlspecialchars($d) . ' — siliniyor', 'warn');
        @unlink($d);
    }
    if (!is_dir($d)) {
        if (@mkdir($d, 0775, true)) {
            out('✓ Oluşturuldu: ' . htmlspecialchars($d), 'ok');
        } else {
            $err = error_get_last()['message'] ?? 'bilinmeyen';
            $parent = dirname($d);
            out('✗ Oluşturulamadı: ' . htmlspecialchars($d) . ' — sebep: ' . htmlspecialchars($err) . ' | parent yazılabilir: ' . (is_writable($parent) ? 'YES' : 'NO') . ' | parent perm: ' . substr(sprintf('%o', fileperms($parent)), -4), 'err');
            continue;
        }
    }
    @chmod($d, 0775);
    if (!is_writable($d)) {
        out('⚠ Yazılamıyor: ' . htmlspecialchars($d) . ' | perm: ' . substr(sprintf('%o', fileperms($d)), -4), 'warn');
    } else {
        out('✓ Yazılabilir: ' . htmlspecialchars($d), 'ok');
    }
}

// ── 7. Migrationları çalıştır ───────────────────────────────────────
out('Migrationlar çalıştırılıyor...');
try {
    require_once BASE_PATH . '/vendor/autoload.php';
    $pdo = \App\Core\Database::pdo();
    $files = glob(BASE_PATH . '/database/migrations/*.sql');
    sort($files);
    foreach ($files as $file) {
        $name = basename($file);
        try {
            $sql = file_get_contents($file);
            // Çoklu statement desteği için noktalı virgülle ayrılmış parçaları çalıştır
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
                if ($stmt === '' || str_starts_with($stmt, '--')) continue;
                $pdo->exec($stmt);
            }
            out('  ✓ ' . htmlspecialchars($name), 'ok');
        } catch (\PDOException $e) {
            $msg = $e->getMessage();
            // Idempotent migration'lar için bilinen "zaten var" hatalarını warn olarak işaretle
            if (str_contains($msg, 'Duplicate') || str_contains($msg, 'already exists') || str_contains($msg, "Can't DROP")) {
                out('  ↷ ' . htmlspecialchars($name) . ' (zaten uygulanmış)', 'warn');
            } else {
                out('  ✗ ' . htmlspecialchars($name) . ': ' . htmlspecialchars($msg), 'err');
            }
        }
    }
} catch (\Throwable $e) {
    out('Migration sistemi başlatılamadı: ' . htmlspecialchars($e->getMessage()), 'err');
}

// ── 8. Opcache reset (varsa) ────────────────────────────────────────
if (function_exists('opcache_reset')) {
    @opcache_reset();
    out('✓ Opcache sıfırlandı.', 'ok');
}

out('<strong style="color:#4ade80">🎉 Güncelleme tamamlandı.</strong>', '');
out('<a href="/admin" style="color:#60a5fa">→ Admin panele git</a>', '');

?></div></body></html>
