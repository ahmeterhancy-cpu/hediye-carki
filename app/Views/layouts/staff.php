<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Görevli', ENT_QUOTES, 'UTF-8') ?> — Hediye Çarkı</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/assets/css/app.css">
<link rel="preload" as="image" href="/img/bg-mall.jpg">
<style>
  :root {
    --bg-image: url('/img/bg-mall.jpg');
  }

  html, body { height: 100%; }

  body {
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    background: var(--bg-image) center/cover no-repeat fixed, #1a0a00;
    position: relative;
  }

  /* Karartma + sıcak ton + vinyet */
  body::before {
    content: '';
    position: fixed;
    inset: 0;
    background:
      radial-gradient(ellipse at center, rgba(0,0,0,0.35) 0%, rgba(0,0,0,0.85) 100%),
      linear-gradient(180deg, rgba(255,140,0,0.10) 0%, rgba(0,0,0,0.55) 100%);
    pointer-events: none;
    z-index: 0;
  }

  /* Üst-alt ışık şeritleri (vurgu) */
  body::after {
    content: '';
    position: fixed;
    inset: 0;
    background:
      linear-gradient(180deg, rgba(255,180,80,0.18) 0%, transparent 12%),
      linear-gradient(0deg,   rgba(255,80,40,0.18)  0%, transparent 12%);
    pointer-events: none;
    z-index: 0;
  }

  /* Scanline / soft texture (premium feel) */
  body { background-attachment: fixed; }

  header, main { position: relative; z-index: 1; }
</style>
</head>
<body class="min-h-screen flex flex-col">

<?php if (\App\Core\Auth::staffId()): ?>
<header class="bg-black/55 backdrop-blur-md text-white px-4 py-2 flex items-center justify-between text-sm border-b border-white/10">
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
