<?php
$code      = $_SESSION['issued_code'] ?? null;
$error     = $_SESSION['staff_error'] ?? null;
$message   = $_SESSION['staff_message'] ?? null;
$settings  = \App\Models\Settings::all();
unset($_SESSION['staff_error'], $_SESSION['staff_message']);

// Kod detayını bul
$codeRow = null;
if ($code) {
    $svc = new \App\Services\CodeService();
    $codeRow = $svc->validate($code);
    if (!$codeRow) {
        unset($_SESSION['issued_code']);
        $code = null;
    }
}

ob_start(); ?>

<?php if ($error): ?>
  <div class="mb-4 bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded text-sm text-center">
    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
  </div>
<?php endif; ?>
<?php if ($message): ?>
  <div class="mb-4 bg-blue-50 border border-blue-300 text-blue-700 px-4 py-3 rounded text-sm text-center">
    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
  </div>
<?php endif; ?>

<?php if ($code && $codeRow): ?>
  <!-- KOD GÖSTERİM EKRANI -->
  <div class="bg-white rounded-2xl shadow-lg p-8 text-center">
    <p class="text-slate-500 text-sm mb-2">Müşteriye verin:</p>
    <div class="text-8xl font-mono font-black tracking-[0.2em] text-slate-800 my-4" id="codeDisplay">
      <?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>
    </div>
    <p class="text-sm text-slate-500 mb-1">Kalan süre:</p>
    <p class="text-2xl font-bold text-orange-500 mb-6" id="countdown">--:--</p>

    <div class="flex gap-3">
      <form method="POST" action="/staff/cancel/<?= (int)$codeRow['id'] ?>" class="flex-1">
        <?= \App\Core\Csrf::field() ?>
        <button type="submit"
                class="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-3 rounded-xl">
          ✗ Kodu İptal Et
        </button>
      </form>
      <form method="POST" action="/staff/approve" class="flex-1">
        <?= \App\Core\Csrf::field() ?>
        <button type="submit"
                onclick="document.getElementById('approved_code_id').value='<?= $codeRow['id'] ?>'"
                class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 rounded-xl">
          + Yeni Onay
        </button>
      </form>
    </div>
  </div>

  <script>
  (function() {
    const expiresAt = new Date('<?= $codeRow['expires_at'] ?>').getTime();
    function tick() {
      const left = Math.max(0, expiresAt - Date.now());
      const m = String(Math.floor(left / 60000)).padStart(2, '0');
      const s = String(Math.floor((left % 60000) / 1000)).padStart(2, '0');
      document.getElementById('countdown').textContent = m + ':' + s;
      if (left > 0) setTimeout(tick, 1000);
      else {
        document.getElementById('countdown').textContent = 'SÜRESİ DOLDU';
        document.getElementById('countdown').className = 'text-2xl font-bold text-red-500 mb-6';
      }
    }
    tick();
  })();
  </script>

<?php else: ?>
  <!-- ONAY EKRANI -->
  <div class="bg-white rounded-2xl shadow-lg p-8">
    <form method="POST" action="/staff/approve" class="space-y-4">
      <?= \App\Core\Csrf::field() ?>
      <div>
        <label class="block text-sm text-slate-500 mb-1">Fiş No <span class="text-slate-400">(opsiyonel)</span></label>
        <input type="text" name="receipt_no" placeholder="örn: F-2024-1234"
               class="w-full border border-slate-300 rounded-xl px-4 py-3 text-slate-700 text-lg focus:outline-none focus:border-green-500">
      </div>
      <div>
        <label class="block text-sm text-slate-500 mb-1">Fiş Tutarı <span class="text-slate-400">(TL, opsiyonel)</span></label>
        <input type="number" name="receipt_amount" min="0" step="0.01"
               placeholder="örn: 750.00"
               class="w-full border border-slate-300 rounded-xl px-4 py-3 text-slate-700 text-lg focus:outline-none focus:border-green-500">
      </div>
      <button type="submit"
              class="w-full bg-green-500 hover:bg-green-600 active:bg-green-700 text-white text-2xl font-black py-6 rounded-2xl transition shadow-lg">
        ✓ ONAYLA
      </button>
    </form>

    <form method="POST" action="/staff/reject" class="mt-3">
      <?= \App\Core\Csrf::field() ?>
      <button type="submit"
              class="w-full bg-slate-200 hover:bg-slate-300 text-slate-600 font-semibold py-3 rounded-xl">
        ✗ REDDET
      </button>
    </form>
  </div>
<?php endif; ?>

<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/staff.php'; ?>
