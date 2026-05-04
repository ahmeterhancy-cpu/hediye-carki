<?php $pageTitle = 'Şartlar'; ob_start(); ?>
<h1 class="text-2xl font-bold mb-6">Etkinlik Şartları</h1>

<form method="POST" action="/admin/settings" class="max-w-xl bg-gray-800 rounded-xl p-6 space-y-4">
  <?= \App\Core\Csrf::field() ?>

  <div class="flex items-center gap-3">
    <input type="checkbox" name="event_active" id="event_active" <?= ($settings['event_active'] ?? '0') === '1' ? 'checked' : '' ?>
           class="w-5 h-5 rounded text-yellow-500">
    <label for="event_active" class="font-semibold">Etkinlik Aktif</label>
  </div>

  <div>
    <label class="block text-sm text-gray-400 mb-1">Etkinlik Başlığı</label>
    <input type="text" name="event_title" value="<?= htmlspecialchars($settings['event_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
           class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white">
  </div>

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="block text-sm text-gray-400 mb-1">Başlangıç Saati</label>
      <input type="time" name="start_time" value="<?= htmlspecialchars($settings['start_time'] ?? '10:00', ENT_QUOTES, 'UTF-8') ?>"
             class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white">
    </div>
    <div>
      <label class="block text-sm text-gray-400 mb-1">Bitiş Saati</label>
      <input type="time" name="end_time" value="<?= htmlspecialchars($settings['end_time'] ?? '22:00', ENT_QUOTES, 'UTF-8') ?>"
             class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white">
    </div>
  </div>

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="block text-sm text-gray-400 mb-1">Telefon Başına Limit</label>
      <input type="number" name="per_phone_limit" min="1" value="<?= (int)($settings['per_phone_limit'] ?? 1) ?>"
             class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white">
    </div>
    <div>
      <label class="block text-sm text-gray-400 mb-1">Limit Penceresi (gün)</label>
      <input type="number" name="per_phone_window" min="1" value="<?= (int)($settings['per_phone_window'] ?? 7) ?>"
             class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white">
    </div>
  </div>

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="block text-sm text-gray-400 mb-1">Min. Harcama (TL, bilgi amaçlı)</label>
      <input type="number" name="min_spend" min="0" value="<?= (int)($settings['min_spend'] ?? 500) ?>"
             class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white">
    </div>
    <div>
      <label class="block text-sm text-gray-400 mb-1">Kod Geçerlilik Süresi (sn)</label>
      <input type="number" name="code_ttl_seconds" min="60" max="3600" value="<?= (int)($settings['code_ttl_seconds'] ?? 300) ?>"
             class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white">
      <p class="text-xs text-gray-500 mt-1">Varsayılan: 300 sn (5 dakika)</p>
    </div>
  </div>

  <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-400 text-gray-900 font-bold py-2 rounded-lg">
    Kaydet
  </button>
</form>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/admin.php'; ?>
