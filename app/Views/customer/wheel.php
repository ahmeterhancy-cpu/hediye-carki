<?php ob_start(); ?>
<style>
#spinBtn {
  width: 200px; height: 200px;
  border-radius: 50%;
  background: linear-gradient(135deg, #FBBF24, #EF4444);
  box-shadow: 0 8px 32px rgba(0,0,0,0.4);
  font-size: 1.5rem;
  font-weight: 900;
  color: white;
  border: 6px solid white;
  cursor: pointer;
  transition: transform 0.1s;
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  z-index: 10;
}
#spinBtn:active { transform: translate(-50%, -50%) scale(0.95); }
#spinBtn:disabled { opacity: 0.6; cursor: not-allowed; }
#wheelWrapper { position: relative; display: inline-block; }
</style>

<div class="text-center">
  <p class="text-white/80 text-lg font-semibold mb-4">
    <?= htmlspecialchars($settings['event_title'] ?? 'Hediye Çarkı', ENT_QUOTES, 'UTF-8') ?>
  </p>

  <!-- İbre -->
  <div class="relative inline-block">
    <div id="wheelWrapper">
      <canvas id="wheelCanvas" width="420" height="420"></canvas>
      <button id="spinBtn">ÇEVİR!</button>
    </div>
    <!-- İbre üstte sabit -->
    <div style="position:absolute; top:-10px; left:50%; transform:translateX(-50%); font-size:2.5rem; filter:drop-shadow(0 2px 4px rgba(0,0,0,0.5));">
      ▼
    </div>
  </div>

  <p class="text-white/60 text-sm mt-4">Butona basın veya dokunun</p>
</div>

<script src="/assets/js/wheel.js"></script>
<script src="/assets/js/confetti.js"></script>
<script>
const prizes = <?= json_encode($prizes) ?>;
const csrfToken = <?= json_encode(\App\Core\Csrf::token()) ?>;

const canvas = document.getElementById('wheelCanvas');
const wheel  = new Wheel(canvas, prizes);
const spinBtn = document.getElementById('spinBtn');

spinBtn.addEventListener('click', async () => {
  spinBtn.disabled = true;
  spinBtn.textContent = '...';

  try {
    const res = await fetch('/api/spin/execute', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: '_csrf=' + encodeURIComponent(csrfToken),
    });

    const data = await res.json();

    if (!data.ok) {
      alert('Hata: ' + (data.error || 'Bilinmeyen hata'));
      spinBtn.disabled = false;
      spinBtn.textContent = 'ÇEVİR!';
      return;
    }

    // Dönüş sesi
    try { new Audio('/assets/sounds/spin.mp3').play(); } catch(e) {}

    await wheel.spinTo(data.target_angle, 5000);

    // Kazanma sesi
    try { new Audio('/assets/sounds/win.mp3').play(); } catch(e) {}

    window.location.href = '/spin/win/' + data.participant_id;

  } catch (e) {
    alert('Bağlantı hatası. Lütfen tekrar deneyin.');
    spinBtn.disabled = false;
    spinBtn.textContent = 'ÇEVİR!';
  }
});
</script>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/customer.php'; ?>
