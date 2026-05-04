<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Admin', ENT_QUOTES, 'UTF-8') ?> — Hediye Çarkı</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = { darkMode: 'class' };
document.documentElement.classList.add('dark');
</script>
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen">

<nav class="bg-gray-800 border-b border-gray-700 px-6 py-3 flex items-center justify-between">
  <div class="flex items-center gap-6">
    <span class="font-bold text-xl text-yellow-400">🎡 Hediye Çarkı</span>
    <a href="/admin" class="text-sm text-gray-300 hover:text-white">Dashboard</a>
    <a href="/admin/prizes" class="text-sm text-gray-300 hover:text-white">Dilimler</a>
    <a href="/admin/stock" class="text-sm text-gray-300 hover:text-white">Stok</a>
    <a href="/admin/settings" class="text-sm text-gray-300 hover:text-white">Şartlar</a>
    <a href="/admin/staff-users" class="text-sm text-gray-300 hover:text-white">Görevliler</a>
    <a href="/admin/participants" class="text-sm text-gray-300 hover:text-white">Katılımcılar</a>
    <a href="/admin/reports" class="text-sm text-gray-300 hover:text-white">Raporlar</a>
  </div>
  <div class="flex items-center gap-4">
    <span class="text-sm text-gray-400"><?= htmlspecialchars(\App\Core\Auth::adminName(), ENT_QUOTES, 'UTF-8') ?></span>
    <form method="POST" action="/admin/logout">
      <?= \App\Core\Csrf::field() ?>
      <button type="submit" class="text-sm text-red-400 hover:text-red-300">Çıkış</button>
    </form>
  </div>
</nav>

<main class="p-6">
<?php if (!empty($_SESSION['flash_error'])): ?>
  <div class="mb-4 bg-red-900/50 border border-red-600 text-red-300 px-4 py-3 rounded">
    <?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?>
  </div>
  <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_pin'])): ?>
  <?php $fp = $_SESSION['flash_pin']; unset($_SESSION['flash_pin']); ?>
  <div class="mb-4 bg-green-900/50 border border-green-600 text-green-300 px-4 py-4 rounded">
    <p class="font-semibold mb-2">✓ <?= htmlspecialchars($fp['name'], ENT_QUOTES, 'UTF-8') ?> için PIN:</p>
    <p class="text-4xl font-mono tracking-widest text-white"><?= htmlspecialchars($fp['pin'], ENT_QUOTES, 'UTF-8') ?></p>
    <p class="text-xs mt-2 text-green-400">Bu PIN bir daha gösterilmeyecek. Görevliye iletin.</p>
  </div>
<?php endif; ?>

<?= $content ?? '' ?>
</main>
</body>
</html>
