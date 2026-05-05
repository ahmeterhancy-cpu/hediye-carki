<?php
  $pageTitle = 'Çark';
  if (!isset($settings)) $settings = \App\Models\Settings::all();
  $companyName = trim($settings['company_name'] ?? '');
  $eventTitle  = $settings['event_title'] ?? 'Hediye Çarkı';
  $headerTitle = $companyName !== '' ? $companyName : $eventTitle;

  // Türkçe-aware uppercase (i→İ, ı→I, diğer karakterler için mb_strtoupper)
  $turkUpper = function (string $s): string {
      $s = strtr($s, ['i' => 'İ', 'ı' => 'I']);
      return mb_strtoupper($s, 'UTF-8');
  };
  ob_start();
?>
<style>
/* Çark sahnesi — arka plandan ayrım için büyük yarı saydam disk */
#wheelStage {
  position: relative;
  display: inline-block;
  padding: clamp(40px, 5.5vw, 70px);
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

/* Halkadaki kavisli yazı — sürekli yavaş dönüş */
#wheelStage { position: relative; }
#ringText {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 4;                    /* Çark canvas'ı (z 0) üzerinde, pointer (z 5+) altında */
  animation: ringSpin 40s linear infinite;
  filter: drop-shadow(0 2px 3px rgba(0,0,0,0.85));
  overflow: visible;
}
@keyframes ringSpin {
  from { transform: rotate(0deg);   }
  to   { transform: rotate(360deg); }
}
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
    <!-- Halkadaki kavisli yazılar -->
    <svg id="ringText" viewBox="0 0 200 200" preserveAspectRatio="xMidYMid meet"
         xmlns="http://www.w3.org/2000/svg">
      <defs>
        <!-- Path radius 92: halka ortası (wheelStage padding alanı içinde) -->
        <!-- ÜST kavis: sol → sağ, ÜSTTEN (sweep=0). Text side="right" ile dış tarafta dik. -->
        <path id="arcTop"
              d="M 8,100 A 92,92 0 0,0 192,100"
              fill="none"/>
        <!-- ALT kavis: sol → sağ, ALTTAN (sweep=1). Text default tarafta dik. -->
        <path id="arcBottom"
              d="M 8,100 A 92,92 0 0,1 192,100"
              fill="none"/>

        <linearGradient id="ringTextGrad" x1="0" x2="0" y1="0" y2="1">
          <stop offset="0"   stop-color="#FFFBEA"/>
          <stop offset="0.5" stop-color="#FFD86B"/>
          <stop offset="1"   stop-color="#C28200"/>
        </linearGradient>
      </defs>

      <!-- ÜST: firma adı — side="right" ile path dış tarafına yazılır (dik) -->
      <text font-family="system-ui, sans-serif"
            font-weight="900"
            font-size="9"
            letter-spacing="1.8"
            fill="url(#ringTextGrad)"
            stroke="#2a1800" stroke-width="0.35"
            paint-order="stroke fill">
        <textPath href="#arcTop" side="right" startOffset="50%" text-anchor="middle">
          <?= htmlspecialchars($turkUpper($headerTitle), ENT_QUOTES, 'UTF-8') ?>
        </textPath>
      </text>

      <?php if (!empty($eventTitle) && trim($eventTitle) !== trim($headerTitle)): ?>
      <!-- ALT: etkinlik adı — alt yarıda default tarafta dik durur -->
      <text font-family="system-ui, sans-serif"
            font-weight="700"
            font-size="7.5"
            letter-spacing="1.5"
            fill="rgba(255,235,150,0.95)"
            stroke="#2a1800" stroke-width="0.3"
            paint-order="stroke fill">
        <textPath href="#arcBottom" startOffset="50%" text-anchor="middle">
          <?= htmlspecialchars($turkUpper($eventTitle), ENT_QUOTES, 'UTF-8') ?>
        </textPath>
      </text>
      <?php endif; ?>
    </svg>

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

<!-- ── Modal ─────────────────────────────────────────────────────── -->
<div id="spinModal" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4"
     style="background: rgba(0,0,0,0.6); backdrop-filter: blur(8px);">
  <div class="rounded-3xl border-4 border-yellow-400 max-w-md w-full p-7 text-center transform transition-transform"
       id="spinModalBox"
       style="background: rgba(255,255,255,0.96);
              backdrop-filter: blur(12px);
              box-shadow: 0 0 80px 10px rgba(255,200,50,0.55), 0 30px 60px rgba(0,0,0,0.7);
              animation: modalIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);">
    <div id="modalIcon" class="text-7xl mb-3"></div>
    <h2 id="modalTitle" class="text-2xl font-black text-slate-800 mb-2"></h2>
    <p id="modalMsg" class="text-slate-700 text-base mb-6"></p>
    <button id="modalBtn" type="button"
            class="px-10 py-3 rounded-2xl text-white text-lg font-black transition active:scale-95"
            style="background: linear-gradient(135deg, #FBBF24, #EF4444);
                   box-shadow: 0 8px 20px rgba(0,0,0,0.4);">
      Tamam
    </button>
  </div>
</div>

<style>
@keyframes modalIn {
  from { opacity: 0; transform: scale(0.7) translateY(-30px); }
  to   { opacity: 1; transform: scale(1)   translateY(0); }
}
</style>

<script src="/assets/js/wheel.js"></script>
<script src="/assets/js/confetti.js"></script>
<script>
const prizes = <?= json_encode($prizes) ?>;
const csrfToken = <?= json_encode(\App\Core\Csrf::token()) ?>;

const wheel   = new Wheel(document.getElementById('wheelCanvas'), prizes);
const spinBtn = document.getElementById('spinBtn');

// ── Custom modal ────────────────────────────────────────────
const modalEl   = document.getElementById('spinModal');
const modalIcon = document.getElementById('modalIcon');
const modalTitle = document.getElementById('modalTitle');
const modalMsg  = document.getElementById('modalMsg');
const modalBtn  = document.getElementById('modalBtn');

function showModal({ icon, title, message, redirectTo, buttonText = 'Tamam' }) {
  modalIcon.textContent  = icon;
  modalTitle.textContent = title;
  modalMsg.textContent   = message;
  modalBtn.textContent   = buttonText;
  modalEl.classList.remove('hidden');

  return new Promise(resolve => {
    const close = () => {
      modalEl.classList.add('hidden');
      modalBtn.removeEventListener('click', close);
      if (redirectTo) location.href = redirectTo;
      resolve();
    };
    modalBtn.addEventListener('click', close, { once: true });
  });
}

const errorMap = {
  no_stock: {
    icon: '📦', title: 'Stok Tükendi',
    message: 'Üzgünüz, hediye stoğu kalmadı. Lütfen görevliyle iletişime geçin.',
  },
  invalid_session: {
    icon: '🔄', title: 'Oturum Süresi Doldu',
    message: 'Lütfen baştan başlayın.', redirectTo: '/staff',
  },
  invalid_csrf: {
    icon: '🔒', title: 'Güvenlik Hatası',
    message: 'Sayfayı yenileyin ve tekrar deneyin.', redirectTo: '/staff',
  },
  already_spun: {
    icon: '✋', title: 'Zaten Çevrildi',
    message: 'Bu müşteri çarkı bir kez çevirmiş.', redirectTo: '/staff',
  },
  server_error: {
    icon: '⚠️', title: 'Sistem Hatası',
    message: 'Bir sorun oluştu. Lütfen tekrar deneyin.',
  },
  network_error: {
    icon: '📡', title: 'Bağlantı Hatası',
    message: 'İnternet bağlantınızı kontrol edip tekrar deneyin.',
  },
};

spinBtn.addEventListener('click', async () => {
  spinBtn.disabled = true;
  spinBtn.textContent = '...';

  try {
    const res  = await fetch('/staff/spin/execute', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: '_csrf=' + encodeURIComponent(csrfToken),
    });
    const data = await res.json();

    if (!data.ok) {
      const cfg = errorMap[data.error] || {
        icon: '⚠️', title: 'Hata', message: data.error || 'Bilinmeyen hata',
      };
      await showModal(cfg);

      if (!cfg.redirectTo) {
        spinBtn.disabled = false;
        spinBtn.textContent = 'ÇEVİR';
      }
      return;
    }

    await wheel.spinTo(data.target_angle, 5000);

    location.href = '/staff/win/' + data.participant_id;

  } catch (e) {
    await showModal(errorMap.network_error);
    spinBtn.disabled = false;
    spinBtn.textContent = 'ÇEVİR';
  }
});
</script>

<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/staff.php'; ?>
