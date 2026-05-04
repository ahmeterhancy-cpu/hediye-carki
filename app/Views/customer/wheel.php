<?php ob_start(); ?>
<style>
#wheelWrapper {
  position: relative;
  display: inline-block;
  width: min(85vw, 80vh, 600px);
  aspect-ratio: 1 / 1;
}
#wheelCanvas {
  width: 100%;
  height: 100%;
  display: block;
}
#spinBtn {
  width: 28%; height: 28%;
  border-radius: 50%;
  background: linear-gradient(135deg, #FBBF24, #EF4444);
  box-shadow: 0 8px 32px rgba(0,0,0,0.4);
  font-size: clamp(1rem, 3vw, 1.5rem);
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
#pointer {
  position: absolute;
  top: -8px; left: 50%;
  transform: translateX(-50%);
  font-size: clamp(2rem, 5vw, 3rem);
  filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));
  z-index: 5;
}
</style>

<div class="text-center w-full px-2">
  <p class="text-white/90 text-xl md:text-2xl font-bold mb-4 drop-shadow">
    <?= htmlspecialchars($settings['event_title'] ?? 'Hediye Çarkı', ENT_QUOTES, 'UTF-8') ?>
  </p>

  <div id="wheelWrapper">
    <canvas id="wheelCanvas" width="600" height="600"></canvas>
    <button id="spinBtn">ÇEVİR!</button>
    <div id="pointer">▼</div>
  </div>

  <p class="text-white/70 text-sm mt-4">Butona dokunun</p>
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
      // CSRF/session sorunlarında sessizce baştan başlat
      const resetErrors = ['invalid_csrf', 'invalid_session', 'code_consumed'];
      if (resetErrors.includes(data.error)) {
        await fetch('/spin/reset', { method: 'POST' }).catch(() => {});
        location.href = '/';
        return;
      }

      const messages = {
        no_stock:     'Üzgünüz, hediye stoğu tükendi. Lütfen görevliye haber verin.',
        server_error: 'Sistem hatası. Lütfen görevliye haber verin.',
      };
      alert(messages[data.error] || ('Hata: ' + data.error));
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
