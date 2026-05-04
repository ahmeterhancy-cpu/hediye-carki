<?php $pageTitle = 'Yeni Katılım'; ob_start(); ?>

<div class="w-full max-w-md">

  <?php if ($error): ?>
    <div class="mb-4 bg-red-500/90 text-white px-4 py-3 rounded-xl text-center font-semibold shadow-lg">
      ⚠ <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <?php if ($message): ?>
    <div class="mb-4 bg-green-500/90 text-white px-4 py-3 rounded-xl text-center font-semibold shadow-lg">
      ✓ <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <?php if (!$eventCheck['ok']): ?>
    <div class="bg-yellow-100 border-2 border-yellow-400 rounded-2xl p-6 text-center text-yellow-900 shadow-xl">
      <div class="text-4xl mb-2">⏸</div>
      <p class="font-bold text-lg">Etkinlik şu anda aktif değil</p>
      <p class="text-sm mt-1">
        <?php if ($eventCheck['reason'] === 'OUT_OF_HOURS'): ?>
          Saatler: <?= htmlspecialchars($settings['start_time'] ?? '', ENT_QUOTES, 'UTF-8') ?> –
                   <?= htmlspecialchars($settings['end_time']   ?? '', ENT_QUOTES, 'UTF-8') ?>
        <?php else: ?>
          Admin panelinden etkinliği aktifleştirin.
        <?php endif; ?>
      </p>
    </div>
  <?php else: ?>

    <div class="text-center mb-4">
      <div class="text-5xl mb-2">📝</div>
      <h1 class="text-3xl font-black text-white drop-shadow">Yeni Katılım</h1>
      <p class="text-white/80 text-sm">Müşteri bilgilerini girin</p>
    </div>

    <form method="POST" action="/staff/customer" class="rounded-2xl p-6 space-y-3 border-2 border-yellow-400"
          style="background-color: rgba(255,255,255,0.92);
                 backdrop-filter: blur(10px);
                 -webkit-backdrop-filter: blur(10px);
                 box-shadow: 0 0 60px rgba(255,180,40,0.4), 0 20px 50px rgba(0,0,0,0.6);">
      <?= \App\Core\Csrf::field() ?>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-slate-600 text-xs font-semibold mb-1">Ad *</label>
          <input type="text" name="first_name" required autofocus
                 class="w-full border-2 border-slate-300 rounded-xl px-3 py-3 text-slate-800 text-lg focus:outline-none focus:border-orange-500"
                 placeholder="Adı">
        </div>
        <div>
          <label class="block text-slate-600 text-xs font-semibold mb-1">Soyad *</label>
          <input type="text" name="last_name" required
                 class="w-full border-2 border-slate-300 rounded-xl px-3 py-3 text-slate-800 text-lg focus:outline-none focus:border-orange-500"
                 placeholder="Soyadı">
        </div>
      </div>

      <div>
        <label class="block text-slate-600 text-xs font-semibold mb-1">Telefon *</label>
        <div class="flex gap-2">
          <select name="phone_prefix"
                  class="border-2 border-slate-300 rounded-xl px-2 py-3 text-slate-800 text-base focus:outline-none focus:border-orange-500">
            <option value="+90">🇹🇷 +90</option>
            <option value="+357">🇨🇾 +357</option>
            <option value="+44">🇬🇧 +44</option>
            <option value="+7">🇷🇺 +7</option>
            <option value="+49">🇩🇪 +49</option>
          </select>
          <input type="tel" name="phone" required inputmode="numeric"
                 class="flex-1 border-2 border-slate-300 rounded-xl px-3 py-3 text-slate-800 text-lg focus:outline-none focus:border-orange-500"
                 placeholder="5XX XXX XX XX">
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-200">
        <div>
          <label class="block text-slate-500 text-xs mb-1">Fiş No <span class="text-slate-400">(ops.)</span></label>
          <input type="text" name="receipt_no"
                 class="w-full border border-slate-300 rounded-lg px-3 py-2 text-slate-800 text-sm">
        </div>
        <div>
          <label class="block text-slate-500 text-xs mb-1">Tutar TL <span class="text-slate-400">(ops.)</span></label>
          <input type="number" name="receipt_amount" min="0" step="0.01"
                 class="w-full border border-slate-300 rounded-lg px-3 py-2 text-slate-800 text-sm">
        </div>
      </div>

      <label class="flex items-start gap-2 pt-1">
        <input type="checkbox" name="kvkk" required class="mt-1 w-5 h-5 rounded text-orange-500">
        <span class="text-slate-600 text-xs">
          Müşteri, kişisel verilerinin etkinlik kapsamında işlenmesini kabul etti.
        </span>
      </label>

      <button type="submit"
              class="w-full bg-gradient-to-r from-orange-500 to-red-600 text-white text-2xl font-black py-4 rounded-2xl shadow-lg active:scale-95 transition mt-2">
        Çarka Geç →
      </button>
    </form>

  <?php endif; ?>

</div>

<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/staff.php'; ?>
