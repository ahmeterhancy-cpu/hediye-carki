<?php $pageTitle = 'Çark'; ob_start(); ?>
<style>
/* Çark sahnesi — arka plandan ayrım için büyük yarı saydam disk */
#wheelStage {
  position: relative;
  display: inline-block;
  padding: clamp(28px, 4vw, 48px);
  border-radius: 50%;
  background:
    radial-gradient(circle at 50% 30%, rgba(255,215,140,0.25) 0%, transparent 55%),
    radial-gradient(circle at 50% 50%, rgba(0,0,0,0.55) 0%, rgba(0,0,0,0.85) 80%);
  box-shadow:
    0 0 80px 20px rgba(255,180,60,0.35),
    0 0 0 6px rgba(255,215,0,0.4),
    0 0 0 14px rgba(0,0,0,0.65),
    0 30px 60px rgba(0,0,0,0.7);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}

#wheelWrapper {
  position: relative;
  display: inline-block;
  width: min(82vw, 68vh, 600px);
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

/* Stage glow pulse */
@keyframes stagePulse {
  0%, 100% { box-shadow: 0 0 80px 20px rgba(255,180,60,0.35), 0 0 0 6px rgba(255,215,0,0.4),  0 0 0 14px rgba(0,0,0,0.65), 0 30px 60px rgba(0,0,0,0.7); }
  50%      { box-shadow: 0 0 120px 30px rgba(255,180,60,0.55), 0 0 0 6px rgba(255,235,120,0.7), 0 0 0 14px rgba(0,0,0,0.65), 0 30px 60px rgba(0,0,0,0.7); }
}
#wheelStage { animation: stagePulse 3s ease-in-out infinite; }
</style>

<div class="text-center w-full max-w-3xl">

  <div class="inline-block bg-black/55 backdrop-blur-md border border-white/15 rounded-full px-5 py-2 mb-5 shadow-xl">
    <span class="text-white font-bold text-base md:text-lg">
      <?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name'], ENT_QUOTES, 'UTF-8') ?>
    </span>
    <span class="text-white/50 mx-2">·</span>
    <span class="text-white/80 text-sm"><?= htmlspecialchars($customer['phone'], ENT_QUOTES, 'UTF-8') ?></span>
  </div>

  <div id="wheelStage">
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
        <path d="M5 0 H45 V50 L25 78 L5 50 Z" fill="url(#ptrGold)" stroke="#5c3a00" stroke-width="2"/>
        <path d="M9 4 H41 V48 L25 70 L9 48 Z" fill="url(#ptrGrad)"/>
        <ellipse cx="18" cy="14" rx="6" ry="4" fill="rgba(255,255,255,0.45)"/>
      </svg>
    </div>
  </div>

  <p class="text-white/90 text-sm mt-5 drop-shadow">Müşteri butona dokunsun</p>
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
