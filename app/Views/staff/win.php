<?php $pageTitle = 'Kazandı!'; ob_start(); ?>
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
.win-card { animation: bounceIn 0.8s ease-out 0.3s both; }
</style>

<div id="flashOverlay"></div>
<canvas id="confettiCanvas" style="position:fixed;inset:0;pointer-events:none;z-index:99;" width="800" height="600"></canvas>

<div class="win-card text-center px-4 relative z-10 max-w-md w-full">

  <?php if (!empty($prize['logo_path'])): ?>
    <img src="<?= htmlspecialchars($prize['logo_path'], ENT_QUOTES, 'UTF-8') ?>"
         alt="logo" class="mx-auto mb-6 max-h-32 object-contain drop-shadow-xl">
  <?php else: ?>
    <div class="text-8xl mb-4">🎁</div>
  <?php endif; ?>

  <div class="rounded-3xl p-8 border-4 border-yellow-400"
       style="background-color: #ffffff; box-shadow: 0 0 80px 10px rgba(255,200,50,0.55), 0 30px 60px rgba(0,0,0,0.8);">
    <p class="text-slate-700 text-base font-bold mb-2 tracking-wide">TEBRİKLER 🎉</p>

    <p class="text-orange-700 text-lg font-semibold mb-3">
      <?= htmlspecialchars($participant['first_name'] . ' ' . $participant['last_name'], ENT_QUOTES, 'UTF-8') ?>
    </p>

    <h1 class="text-3xl md:text-4xl font-black text-slate-900 mb-2">
      <?= htmlspecialchars($participant['prize_name_snapshot'], ENT_QUOTES, 'UTF-8') ?>
    </h1>

    <?php if ($participant['brand_snapshot'] ?? null): ?>
      <p class="text-xl text-orange-700 font-bold mb-4">
        <?= htmlspecialchars($participant['brand_snapshot'], ENT_QUOTES, 'UTF-8') ?>
      </p>
    <?php endif; ?>

    <div class="bg-orange-50 border-2 border-orange-300 rounded-2xl px-6 py-4 mb-2">
      <p class="text-slate-700 text-sm font-semibold mb-1">📍 Hediyeyi şuradan alın:</p>
      <p class="text-slate-900 font-black text-lg">
        <?php
          $pickup = $participant['pickup_snapshot']
                 ?? ($participant['brand_snapshot'] ? $participant['brand_snapshot'] . ' standı' : 'Etkinlik standı');
          echo htmlspecialchars($pickup, ENT_QUOTES, 'UTF-8');
        ?>
      </p>
    </div>

    <p class="text-slate-500 text-xs font-mono mt-2">Katılım #<?= (int)$participant['id'] ?></p>
  </div>

  <form method="POST" action="/staff/new" class="mt-6">
    <?= \App\Core\Csrf::field() ?>
    <button type="submit"
            class="bg-white text-orange-700 hover:bg-yellow-100 active:scale-95 transition px-10 py-4 rounded-2xl text-lg font-black border-2 border-yellow-400"
            style="box-shadow: 0 0 30px rgba(255,200,50,0.6), 0 10px 30px rgba(0,0,0,0.5);">
      + Yeni Müşteri
    </button>
  </form>
</div>

<script src="/assets/js/confetti.js"></script>
<script>
startConfetti(document.getElementById('confettiCanvas'));
</script>

<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/staff.php'; ?>
