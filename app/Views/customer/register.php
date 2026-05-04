<?php ob_start(); ?>
<div class="w-full max-w-sm px-4">
  <div class="text-center mb-6">
    <div class="text-5xl mb-2">📝</div>
    <h1 class="text-2xl font-black text-white">Bilgilerinizi Girin</h1>
    <p class="text-white/80 text-sm mt-1">Hediye çarkını çevirebilmek için</p>
  </div>

  <?php if ($error): ?>
    <div class="mb-4 bg-red-500/80 text-white px-4 py-3 rounded-xl text-center text-sm font-semibold">
      <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="/spin/register" class="bg-white/95 rounded-2xl shadow-xl p-6 space-y-4">
    <?= \App\Core\Csrf::field() ?>

    <div>
      <label class="block text-slate-600 text-sm font-semibold mb-1">Ad *</label>
      <input type="text" name="first_name" required autofocus
             class="w-full border border-slate-300 rounded-xl px-4 py-3 text-slate-800 text-xl focus:outline-none focus:border-orange-500"
             placeholder="Adınız">
    </div>
    <div>
      <label class="block text-slate-600 text-sm font-semibold mb-1">Soyad *</label>
      <input type="text" name="last_name" required
             class="w-full border border-slate-300 rounded-xl px-4 py-3 text-slate-800 text-xl focus:outline-none focus:border-orange-500"
             placeholder="Soyadınız">
    </div>
    <div>
      <label class="block text-slate-600 text-sm font-semibold mb-1">Telefon *</label>
      <div class="flex gap-2">
        <select name="phone_prefix"
                class="border border-slate-300 rounded-xl px-2 py-3 text-slate-800 text-sm focus:outline-none focus:border-orange-500">
          <option value="+90">🇹🇷 +90</option>
          <option value="+357">🇨🇾 +357</option>
          <option value="+44">🇬🇧 +44</option>
          <option value="+7">🇷🇺 +7</option>
        </select>
        <input type="tel" name="phone" required
               class="flex-1 border border-slate-300 rounded-xl px-4 py-3 text-slate-800 text-xl focus:outline-none focus:border-orange-500"
               placeholder="5XX XXX XX XX">
      </div>
    </div>
    <label class="flex items-start gap-3">
      <input type="checkbox" name="kvkk" required class="mt-1 w-5 h-5 rounded text-orange-500">
      <span class="text-slate-600 text-sm">
        Kişisel verilerimin etkinlik kapsamında işlenmesini kabul ediyorum.
        <a href="#" class="text-orange-600 underline">KVKK Aydınlatma Metni</a>
      </span>
    </label>
    <button type="submit"
            class="w-full bg-gradient-to-r from-orange-500 to-red-600 text-white text-xl font-black py-4 rounded-2xl shadow-lg active:scale-95 transition">
      Çarkı Çevir! 🎡
    </button>
  </form>

  <p class="text-center text-white/60 text-xs mt-4">
    <a href="/" class="underline">← Geri dön</a>
  </p>
</div>

<script>
// Idle reset
let idleTimer;
function resetIdle() {
  clearTimeout(idleTimer);
  idleTimer = setTimeout(() => {
    fetch('/spin/reset', { method: 'POST', headers: { 'X-CSRF-Token': '<?= \App\Core\Csrf::token() ?>' } })
      .finally(() => location.href = '/');
  }, 60000);
}
['touchstart','mousedown','keydown','input'].forEach(e => document.addEventListener(e, resetIdle));
resetIdle();
</script>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/customer.php'; ?>
