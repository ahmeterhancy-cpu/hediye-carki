<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($settings['event_title'] ?? 'Hediye Çarkı', ENT_QUOTES, 'UTF-8') ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/assets/css/app.css">
<style>
  body { touch-action: manipulation; -webkit-tap-highlight-color: transparent; user-select: none; }
</style>
</head>
<body class="bg-gradient-to-br from-yellow-400 via-orange-500 to-red-600 min-h-screen flex flex-col items-center justify-center overflow-hidden">
<?= $content ?? '' ?>
</body>
</html>
