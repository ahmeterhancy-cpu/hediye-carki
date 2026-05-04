<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Görevli', ENT_QUOTES, 'UTF-8') ?> — Hediye Çarkı</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/assets/css/app.css">
<style>
  body { touch-action: manipulation; -webkit-tap-highlight-color: transparent; }
</style>
</head>
<body class="bg-gradient-to-br from-orange-400 via-orange-500 to-red-600 min-h-screen flex flex-col">

<?php if (\App\Core\Auth::staffId()): ?>
<header class="bg-black/30 backdrop-blur text-white px-4 py-2 flex items-center justify-between text-sm">
  <span class="font-semibold">
    🎡 <?= htmlspecialchars($settings['event_title'] ?? 'Hediye Çarkı', ENT_QUOTES, 'UTF-8') ?>
  </span>
  <div class="flex items-center gap-3">
    <span class="opacity-80">Görevli: <?= htmlspecialchars(\App\Core\Auth::staffName(), ENT_QUOTES, 'UTF-8') ?></span>
    <form method="POST" action="/staff/logout">
      <?= \App\Core\Csrf::field() ?>
      <button type="submit" class="text-red-200 hover:text-white">Çıkış</button>
    </form>
  </div>
</header>
<?php endif; ?>

<main class="flex-1 flex items-center justify-center p-4">
<?= $content ?? '' ?>
</main>

</body>
</html>
