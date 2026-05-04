<?php ob_start(); ?>
<div class="w-full max-w-sm px-4">
  <!-- Logo / Başlık -->
  <div class="text-center mb-8">
    <div class="text-7xl mb-3">🎡</div>
    <h1 class="text-3xl font-black text-white drop-shadow">
      <?= htmlspecialchars($settings['event_title'] ?? 'Hediye Çarkı', ENT_QUOTES, 'UTF-8') ?>
    </h1>
    <p class="text-white/80 mt-1 text-lg">Kodunuzu girin, çarkı çevirin!</p>
  </div>

  <?php if ($error): ?>
    <div class="mb-4 bg-red-500/80 text-white px-4 py-3 rounded-xl text-center font-semibold" id="errorMsg">
      <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="/spin/code" id="codeForm">
    <?= \App\Core\Csrf::field() ?>
    <input type="hidden" name="code" id="codeValue">

    <!-- 6 kutu -->
    <div class="flex justify-center gap-2 mb-6">
      <?php for ($i = 0; $i < 6; $i++): ?>
        <div class="w-14 h-16 bg-white/90 rounded-2xl flex items-center justify-center text-3xl font-black text-slate-800 shadow-lg code-box border-4 border-transparent transition-all" id="cb<?= $i ?>">
          &nbsp;
        </div>
      <?php endfor; ?>
    </div>

    <!-- Tuş takımı -->
    <div class="grid grid-cols-3 gap-3">
      <?php foreach ([1,2,3,4,5,6,7,8,9] as $n): ?>
        <button type="button" onclick="codePress(<?= $n ?>)"
                class="bg-white/90 hover:bg-white active:scale-95 text-slate-800 text-3xl font-black py-5 rounded-2xl shadow transition select-none">
          <?= $n ?>
        </button>
      <?php endforeach; ?>
      <button type="button" onclick="codeClear()"
              class="bg-red-500/90 hover:bg-red-500 text-white text-lg font-bold py-5 rounded-2xl shadow transition select-none">
        SİL
      </button>
      <button type="button" onclick="codePress(0)"
              class="bg-white/90 hover:bg-white text-slate-800 text-3xl font-black py-5 rounded-2xl shadow transition select-none">
        0
      </button>
      <button type="submit" id="codeSubmit" disabled
              class="bg-emerald-500 disabled:bg-white/30 disabled:text-white/40 text-white text-2xl font-black py-5 rounded-2xl shadow transition select-none">
        ↵
      </button>
    </div>
  </form>
</div>

<script>
let digits = [];

function updateCodeDisplay() {
  for (let i = 0; i < 6; i++) {
    const box = document.getElementById('cb' + i);
    box.textContent = digits[i] !== undefined ? digits[i] : ' ';
    box.classList.toggle('border-white', i === digits.length);
    box.classList.toggle('border-transparent', i !== digits.length);
    box.classList.toggle('scale-110', i === digits.length);
  }
  document.getElementById('codeSubmit').disabled = digits.length < 6;
}

function codePress(n) {
  if (digits.length >= 6) return;
  digits.push(n);
  updateCodeDisplay();
  if (digits.length === 6) {
    document.getElementById('codeValue').value = digits.join('');
    setTimeout(() => document.getElementById('codeForm').submit(), 200);
  }
}

function codeClear() {
  digits.pop();
  updateCodeDisplay();
}

document.addEventListener('keydown', e => {
  if (e.key >= '0' && e.key <= '9') codePress(parseInt(e.key));
  else if (e.key === 'Backspace') codeClear();
});

// Idle reset: 60 sn hareketsizlikte sıfırla
let idleTimer;
function resetIdle() {
  clearTimeout(idleTimer);
  idleTimer = setTimeout(() => {
    fetch('/spin/reset', { method: 'POST', headers: { 'X-CSRF-Token': '<?= \App\Core\Csrf::token() ?>' } })
      .finally(() => location.href = '/');
  }, 60000);
}
['touchstart','mousedown','keydown'].forEach(e => document.addEventListener(e, resetIdle));
resetIdle();
updateCodeDisplay();
</script>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/customer.php'; ?>
