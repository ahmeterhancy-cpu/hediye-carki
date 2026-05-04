<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Görevli Paneli — Hediye Çarkı</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="bg-slate-100 min-h-screen">

<header class="bg-white shadow px-6 py-3 flex items-center justify-between">
  <span class="font-bold text-lg text-slate-700">🎡 Hediye Çarkı — Görevli</span>
  <div class="flex items-center gap-4">
    <?php if (\App\Core\Auth::staffId()): ?>
      <span class="text-sm text-slate-500"><?= htmlspecialchars(\App\Core\Auth::staffName(), ENT_QUOTES, 'UTF-8') ?></span>
      <form method="POST" action="/staff/logout">
        <?= \App\Core\Csrf::field() ?>
        <button type="submit" class="text-sm text-red-500 hover:text-red-700">Çıkış</button>
      </form>
    <?php endif; ?>
  </div>
</header>

<main class="p-6 max-w-lg mx-auto">
<?= $content ?? '' ?>
</main>
</body>
</html>
