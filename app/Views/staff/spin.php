<?php $pageTitle = 'Çark'; ob_start(); ?>
<style>
#wheelWrapper {
  position: relative;
  display: inline-block;
  width: min(85vw, 75vh, 640px);
  aspect-ratio: 1 / 1;
}
#wheelCanvas { width: 100%; height: 100%; display: block; }

#spinBtn {
  width: 16%; height: 16%;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%, #FFFFFF, #FFD86B 55%, #C28200);
  box-shadow:
    0 0 0 4px #5c3a00,
    0 6px 16px rgba(0,0,0,0.5),
    inset 0 -4px 8px rgba(0,0,0,0.2),
    inset 0 4px 8px rgba(255,255,255,0.7);
  font-size: clamp(0.7rem, 1.6vw, 1.05rem);
  font-weight: 900;
  letter-spacing: 0.5px;
  color: #5c3a00;
  text-shadow: 0 1px 0 rgba(255,255,255,0.6);
  border: none;
  cursor: pointer;
  transition: transform 0.1s, box-shadow 0.1s;
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  z-index: 10;
  outline: none;
}
#spinBtn:active {
  transform: translate(-50%, -50%) scale(0.93);
  box-shadow:
    0 0 0 4px #5c3a00,
    0 3px 8px rgba(0,0,0,0.5),
    inset 0 4px 8px rgba(0,0,0,0.25);
}
#spinBtn:disabled { opacity: 0.7; cursor: not-allowed; }

/* Gerçekçi pointer — 3D efektli ok */
#pointer {
  position: absolute;
  top: -2%;
  left: 50%;
  transform: translateX(-50%);
  width: clamp(28px, 6vw, 50px);
  height: clamp(48px, 10vw, 80px);
  z-index: 20;
  filter: drop-shadow(0 4px 6px rgba(0,0,0,0.55));
}

/* Çark glow */
#wheelWrapper::before {
  content: '';
  position: absolute;
  inset: -10px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(255,215,0,0.35) 0%, transparent 65%);
  z-index: -1;
  animation: wheelPulse 2.5s ease-in-out infinite;
}
@keyframes wheelPulse {
  0%, 100% { opacity: 0.55; transform: scale(1); }
  50%      { opacity: 0.85; transform: scale(1.04); }
}
</style>

<div class="text-center w-full max-w-3xl">

  <p class="text-white drop-shadow text-lg md:text-xl mb-2">
    <strong><?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name'], ENT_QUOTES, 'UTF-8') ?></strong>
    <span class="opacity-70">·</span>
    <span class="opacity-80 text-sm"><?= htmlspecialchars($customer['phone'], ENT_QUOTES, 'UTF-8') ?></span>
  </p>

  <div id="wheelWrapper">
    <canvas id="wheelCanvas" width="640" height="640"></canvas>
    <button id="spinBtn">ÇEVİR</button>

    <!-- 3D pointer (SVG) -->
    <svg id="pointer" viewBox="0 0 50 80" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <linearGradient id="ptrGrad" x1="0" x2="1" y1="0" y2="0">
          <stop offset="0"   stop-color="#B71C1C"/>
          <stop offset="0.5" stop-color="#EF5350"/>
          <stop offset="1"   stop-color="#7F0000"/>
        </linearGradient>
        <linearGradient id="ptrGold" x1="0" x2="0" y1="0" y2="1">
          <stop offset="0" stop-color="#FFE9A0"/>
          <stop offset="1" stop-color="#C28200"/>
        </linearGradient>
      </defs>
      <!-- Çerçeve (altın) -->
      <path d="M5 0 H45 V50 L25 78 L5 50 Z" fill="url(#ptrGold)" stroke="#5c3a00" stroke-width="2"/>
      <!-- İç (kırmızı) -->
      <path d="M9 4 H41 V48 L25 70 L9 48 Z" fill="url(#ptrGrad)"/>
      <!-- Parlak nokta -->
      <ellipse cx="18" cy="14" rx="6" ry="4" fill="rgba(255,255,255,0.45)"/>
    </svg>
  </div>

  <p class="text-white/80 text-sm mt-4">Müşteri butona dokunsun</p>
</div>

<script src="/assets/js/wheel.js"></script>
<script src="/assets/js/confetti.js"></script>
<script>
const prizes = <?= json_encode($prizes) ?>;
const csrfToken = <?= json_encode(\App\Core\Csrf::token()) ?>;

const wheel   = new Wheel(document.getElementById('wheelCanvas'), prizes);
const spinBtn = document.getElementById('spinBtn');

spinBtn.addEventListener('click', async () => {
  spinBtn.disabled = true;
  spinBtn.textContent = '...';

  try {
    const res = await fetch('/staff/spin/execute', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: '_csrf=' + encodeURIComponent(csrfToken),
    });

    const data = await res.json();

    if (!data.ok) {
      const messages = {
        no_stock:        'Üzgünüz, hediye stoğu tükendi.',
        invalid_session: 'Oturum hatası. Lütfen baştan başlayın.',
        invalid_csrf:    'Güvenlik hatası. Lütfen baştan başlayın.',
        already_spun:    'Bu müşteri zaten çevirdi.',
        server_error:    'Sistem hatası.',
      };
      alert(messages[data.error] || ('Hata: ' + data.error));

      if (['invalid_session','invalid_csrf','already_spun'].includes(data.error)) {
        location.href = '/staff';
      } else {
        spinBtn.disabled = false;
        spinBtn.textContent = 'ÇEVİR!';
      }
      return;
    }

    try { new Audio('/assets/sounds/spin.mp3').play(); } catch(e) {}

    await wheel.spinTo(data.target_angle, 5000);

    try { new Audio('/assets/sounds/win.mp3').play(); } catch(e) {}

    location.href = '/staff/win/' + data.participant_id;

  } catch (e) {
    alert('Bağlantı hatası. Lütfen tekrar deneyin.');
    spinBtn.disabled = false;
    spinBtn.textContent = 'ÇEVİR!';
  }
});
</script>

<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/staff.php'; ?>
