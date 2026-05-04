<?php $content = ''; ob_start(); ?>
<div class="text-center mb-8">
  <span class="text-6xl">🎡</span>
  <h1 class="text-2xl font-bold text-slate-700 mt-2">Görevli Girişi</h1>
  <p class="text-slate-500 text-sm">6 haneli PIN'inizi girin</p>
</div>

<?php if (!empty($_SESSION['staff_error'])): ?>
  <div class="mb-4 bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded text-sm text-center">
    <?= htmlspecialchars($_SESSION['staff_error'], ENT_QUOTES, 'UTF-8') ?>
  </div>
  <?php unset($_SESSION['staff_error']); ?>
<?php endif; ?>

<div class="bg-white shadow-lg rounded-2xl p-8">
  <form method="POST" action="/staff/login" id="pinForm">
    <?= \App\Core\Csrf::field() ?>
    <input type="hidden" name="pin" id="pinValue">

    <!-- PIN kutuları -->
    <div class="flex justify-center gap-3 mb-8">
      <?php for ($i = 0; $i < 6; $i++): ?>
        <div class="w-12 h-14 bg-slate-100 border-2 border-slate-300 rounded-xl flex items-center justify-center text-2xl font-bold text-slate-700 pin-box" id="box<?= $i ?>">
          &nbsp;
        </div>
      <?php endfor; ?>
    </div>

    <!-- Tuş takımı -->
    <div class="grid grid-cols-3 gap-3 max-w-xs mx-auto">
      <?php foreach ([1,2,3,4,5,6,7,8,9] as $n): ?>
        <button type="button" onclick="numPress(<?= $n ?>)"
                class="num-key bg-slate-100 hover:bg-slate-200 active:bg-slate-300 text-slate-800 text-2xl font-bold py-4 rounded-xl transition select-none">
          <?= $n ?>
        </button>
      <?php endforeach; ?>
      <button type="button" onclick="numClear()"
              class="num-key bg-red-100 hover:bg-red-200 text-red-700 text-lg font-bold py-4 rounded-xl transition select-none">
        SİL
      </button>
      <button type="button" onclick="numPress(0)"
              class="num-key bg-slate-100 hover:bg-slate-200 text-slate-800 text-2xl font-bold py-4 rounded-xl transition select-none">
        0
      </button>
      <button type="submit" id="submitBtn" disabled
              class="num-key bg-green-500 disabled:bg-slate-200 disabled:text-slate-400 text-white text-xl font-bold py-4 rounded-xl transition select-none">
        ↵
      </button>
    </div>
  </form>
</div>

<script>
let pinDigits = [];

function updateDisplay() {
  for (let i = 0; i < 6; i++) {
    const box = document.getElementById('box' + i);
    box.textContent = pinDigits[i] !== undefined ? '●' : ' ';
    box.classList.toggle('border-yellow-500', i === pinDigits.length);
    box.classList.toggle('border-slate-300', i !== pinDigits.length);
  }
  document.getElementById('submitBtn').disabled = pinDigits.length < 6;
}

function numPress(n) {
  if (pinDigits.length >= 6) return;
  pinDigits.push(n);
  updateDisplay();
  if (pinDigits.length === 6) {
    document.getElementById('pinValue').value = pinDigits.join('');
    setTimeout(() => document.getElementById('pinForm').submit(), 150);
  }
}

function numClear() {
  pinDigits.pop();
  updateDisplay();
}

// Klavye desteği
document.addEventListener('keydown', e => {
  if (e.key >= '0' && e.key <= '9') numPress(parseInt(e.key));
  else if (e.key === 'Backspace') numClear();
});

updateDisplay();
</script>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/staff.php'; ?>
