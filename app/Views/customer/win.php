<?php ob_start(); ?>
<style>
@keyframes flashIn {
  0% { opacity: 0; background: white; }
  5% { opacity: 1; background: white; }
  20% { background: transparent; }
  100% { background: transparent; }
}
#flashOverlay {
  position: fixed; inset: 0;
  animation: flashIn 1s ease-out forwards;
  pointer-events: none;
  z-index: 100;
}

@keyframes bounceIn {
  0% { transform: scale(0.3); opacity: 0; }
  50% { transform: scale(1.05); }
  70% { transform: scale(0.95); }
  100% { transform: scale(1); opacity: 1; }
}
.win-card {
  animation: bounceIn 0.8s ease-out 0.3s both;
}
</style>

<div id="flashOverlay"></div>
<canvas id="confettiCanvas" style="position:fixed;inset:0;pointer-events:none;z-index:99;" width="800" height="600"></canvas>

<div class="win-card text-center px-4 relative z-10 max-w-md w-full">
  <!-- Logo -->
  <?php if (!empty($prize['logo_path'])): ?>
    <img src="<?= htmlspecialchars($prize['logo_path'], ENT_QUOTES, 'UTF-8') ?>"
         alt="logo" class="mx-auto mb-6 max-h-32 object-contain drop-shadow-xl">
  <?php else: ?>
    <div class="text-8xl mb-4">🎁</div>
  <?php endif; ?>

  <div class="bg-white/95 rounded-3xl shadow-2xl p-8">
    <p class="text-slate-500 text-lg font-semibold mb-2">TEBRİKLER! 🎉</p>
    <h1 class="text-4xl font-black text-slate-800 mb-2">
      <?= htmlspecialchars($participant['prize_name_snapshot'], ENT_QUOTES, 'UTF-8') ?>
    </h1>
    <?php if ($participant['brand_snapshot'] ?? null): ?>
      <p class="text-2xl text-orange-600 font-bold mb-4"><?= htmlspecialchars($participant['brand_snapshot'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <div class="bg-orange-50 border border-orange-200 rounded-2xl px-6 py-4 mb-6">
      <p class="text-slate-600 text-sm mb-1">Hediyenizi alın:</p>
      <p class="text-slate-800 font-bold">
        <?= $participant['brand_snapshot'] ? htmlspecialchars($participant['brand_snapshot'], ENT_QUOTES, 'UTF-8') . ' standı' : 'Etkinlik standı' ?>
      </p>
    </div>

    <p class="text-slate-400 text-sm mb-2">Bu ekranı görevliye gösterin</p>
    <p class="text-slate-500 text-xs font-mono">Katılım #<?= (int)$participant['id'] ?></p>
  </div>

  <!-- Auto-reset sayacı -->
  <div class="mt-6 text-white/80 text-sm" id="resetMsg"></div>

  <form method="POST" action="/spin/reset" class="mt-3" id="resetForm">
    <?= \App\Core\Csrf::field() ?>
  </form>
  <button onclick="document.getElementById('resetForm').submit()"
          class="mt-2 bg-white/20 hover:bg-white/30 text-white px-6 py-2 rounded-full text-sm transition">
    Yeni Katılımcı
  </button>
</div>

<script src="/assets/js/confetti.js"></script>
<script>
// Konfeti
startConfetti(document.getElementById('confettiCanvas'));

// 30 sn geri sayım → ana ekran
let left = 30;
const msg = document.getElementById('resetMsg');
const tick = () => {
  msg.textContent = left + ' saniye sonra otomatik sıfırlanır...';
  if (left <= 0) {
    document.getElementById('resetForm').submit();
  } else {
    left--;
    setTimeout(tick, 1000);
  }
};
setTimeout(tick, 1000);
</script>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/customer.php'; ?>
