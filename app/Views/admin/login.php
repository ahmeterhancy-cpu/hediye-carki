<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Giriş — Hediye Çarkı</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>document.documentElement.classList.add('dark');</script>
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center">
<div class="w-full max-w-sm">
  <div class="text-center mb-8">
    <span class="text-5xl">🎡</span>
    <h1 class="text-2xl font-bold text-white mt-2">Hediye Çarkı</h1>
    <p class="text-gray-400 text-sm">Admin Paneli</p>
  </div>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="mb-4 bg-red-900/50 border border-red-600 text-red-300 px-4 py-3 rounded text-sm">
      <?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
  <?php endif; ?>
  <form method="POST" action="/admin/login" class="bg-gray-800 rounded-xl p-6 shadow-xl space-y-4">
    <?= \App\Core\Csrf::field() ?>
    <div>
      <label class="block text-sm text-gray-400 mb-1">E-posta</label>
      <input type="email" name="email" required autofocus
             class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-yellow-500">
    </div>
    <div>
      <label class="block text-sm text-gray-400 mb-1">Parola</label>
      <input type="password" name="password" required
             class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-yellow-500">
    </div>
    <button type="submit"
            class="w-full bg-yellow-500 hover:bg-yellow-400 text-gray-900 font-bold py-2 rounded-lg transition">
      Giriş Yap
    </button>
  </form>
</div>
</body>
</html>
