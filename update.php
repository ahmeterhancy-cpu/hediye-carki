<?php
/**
 * Self-updater — GitHub'tan en son sürümü çeker, dosyaları yeniler.
 * Kullanım: https://siteniz.com/update.php?secret=INSTALL_SECRET
 *
 * .env, storage/*, uploads/*, vendor/*  korunur.
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

$ctx  = stream_context_create(['http' => ['timeout' => 60, 'user_agent' => 'PHP-SelfUpdate']]);
$data = @file_get_contents(REPO_ZIP, false, $ctx);
if (!$data) {
    out('✗ ZIP indirilemedi (allow_url_fopen kapalı veya internet erişimi yok).', 'err');
    exit;
}
file_put_contents($zipPath, $data);
out('✓ ZIP indirildi (' . round(strlen($data) / 1024) . ' KB)', 'ok');

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

// ── 6. Opcache reset (varsa) ────────────────────────────────────────
if (function_exists('opcache_reset')) {
    @opcache_reset();
    out('✓ Opcache sıfırlandı.', 'ok');
}

out('<strong style="color:#4ade80">🎉 Güncelleme tamamlandı.</strong>', '');
out('<a href="/admin" style="color:#60a5fa">→ Admin panele git</a>', '');

?></div></body></html>
