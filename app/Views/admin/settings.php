<?php $pageTitle = 'Şartlar'; ob_start(); ?>
<h1 class="text-2xl font-bold mb-6">Etkinlik Şartları & Marka Yönetimi</h1>

<form method="POST" action="/admin/settings" enctype="multipart/form-data" class="max-w-2xl bg-gray-800 rounded-xl p-6 space-y-5">
  <?= \App\Core\Csrf::field() ?>

  <!-- ── Marka / Branding ──────────────────────────────────────── -->
  <fieldset class="border border-gray-700 rounded-lg p-4">
    <legend class="px-2 text-sm font-semibold text-yellow-400">Marka</legend>

    <div class="mb-3">
      <label class="block text-sm text-gray-400 mb-1">Firma Adı</label>
      <input type="text" name="company_name"
             value="<?= htmlspecialchars($settings['company_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
             placeholder="örn: Erasta Edirne AVM"
             class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white">
      <p class="text-xs text-gray-500 mt-1">Müşteri ekranlarında üst başlıkta gösterilir.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm text-gray-400 mb-1">Firma Logosu</label>
        <?php if (!empty($settings['company_logo_path'])): ?>
          <div class="mb-2 flex items-center gap-2">
            <img src="<?= htmlspecialchars($settings['company_logo_path'], ENT_QUOTES, 'UTF-8') ?>"
                 alt="logo" class="h-16 bg-gray-900 rounded p-2 border border-gray-600">
            <label class="inline-flex items-center gap-1 text-xs text-red-400">
              <input type="checkbox" name="remove_company_logo" value="1"> Kaldır
            </label>
          </div>
        <?php endif; ?>
        <input type="file" name="company_logo" accept="image/*"
               class="w-full text-sm text-gray-400">
        <p class="text-xs text-gray-500 mt-1">PNG/SVG önerilir, maks 5MB.</p>
      </div>

      <div>
        <label class="block text-sm text-gray-400 mb-1">Arka Plan Resmi</label>
        <?php if (!empty($settings['bg_image_path'])): ?>
          <div class="mb-2">
            <img src="<?= htmlspecialchars($settings['bg_image_path'], ENT_QUOTES, 'UTF-8') ?>"
                 alt="bg" class="h-16 w-full object-cover rounded border border-gray-600">
            <label class="inline-flex items-center gap-1 text-xs text-blue-400 mt-1">
              <input type="checkbox" name="reset_bg" value="1"> Default'a sıfırla
            </label>
          </div>
        <?php endif; ?>
        <input type="file" name="bg_image" accept="image/*"
               class="w-full text-sm text-gray-400">
        <p class="text-xs text-gray-500 mt-1">JPG (yatay, 1920x1080+), maks 8MB.</p>
      </div>
    </div>
  </fieldset>

  <!-- ── Etkinlik ──────────────────────────────────────────────── -->
  <fieldset class="border border-gray-700 rounded-lg p-4 space-y-4">
    <legend class="px-2 text-sm font-semibold text-yellow-400">Etkinlik</legend>

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

  </fieldset>

  <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-400 text-gray-900 font-bold py-3 rounded-lg text-lg">
    💾 Kaydet
  </button>
</form>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/admin.php'; ?>
