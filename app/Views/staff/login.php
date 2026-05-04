<?php $pageTitle = 'Görevli Giriş'; ob_start(); ?>

<div class="w-full max-w-sm">
  <div class="text-center mb-6">
    <div class="text-7xl mb-2">🎡</div>
    <h1 class="text-3xl font-black text-white drop-shadow">Görevli Girişi</h1>
    <p class="text-white/80 text-sm mt-1">6 haneli PIN'inizi girin</p>
  </div>

  <?php if (!empty($_SESSION['staff_error'])): ?>
    <div class="mb-4 bg-red-500/90 text-white px-4 py-3 rounded-xl text-center font-semibold shadow-lg">
      <?= htmlspecialchars($_SESSION['staff_error'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php unset($_SESSION['staff_error']); ?>
  <?php endif; ?>

  <div class="rounded-3xl p-6 border-2 border-yellow-400"
       style="background-color: rgba(255,255,255,0.92);
              backdrop-filter: blur(10px);
              -webkit-backdrop-filter: blur(10px);
              box-shadow: 0 0 60px rgba(255,180,40,0.4), 0 20px 50px rgba(0,0,0,0.6);">
    <form method="POST" action="/staff/login" id="pinForm">
      <?= \App\Core\Csrf::field() ?>
      <input type="hidden" name="pin" id="pinValue">

      <div class="flex justify-center gap-2 mb-6">
        <?php for ($i = 0; $i < 6; $i++): ?>
          <div class="w-12 h-14 bg-slate-100 border-2 border-slate-300 rounded-xl flex items-center justify-center text-2xl font-bold text-slate-700" id="box<?= $i ?>">
            &nbsp;
          </div>
        <?php endfor; ?>
      </div>

      <div class="grid grid-cols-3 gap-3">
        <?php foreach ([1,2,3,4,5,6,7,8,9] as $n): ?>
          <button type="button" onclick="numPress(<?= $n ?>)"
                  class="bg-slate-100 hover:bg-slate-200 active:bg-slate-300 text-slate-800 text-2xl font-bold py-4 rounded-xl transition select-none">
            <?= $n ?>
          </button>
        <?php endforeach; ?>
        <button type="button" onclick="numClear()"
                class="bg-red-100 hover:bg-red-200 text-red-700 text-lg font-bold py-4 rounded-xl transition select-none">SİL</button>
        <button type="button" onclick="numPress(0)"
                class="bg-slate-100 hover:bg-slate-200 text-slate-800 text-2xl font-bold py-4 rounded-xl transition select-none">0</button>
        <button type="submit" id="submitBtn" disabled
                class="bg-green-500 disabled:bg-slate-200 disabled:text-slate-400 text-white text-xl font-bold py-4 rounded-xl transition select-none">↵</button>
      </div>
    </form>
  </div>
</div>

<script>
let pinDigits = [];
function updateDisplay() {
  for (let i = 0; i < 6; i++) {
    const box = document.getElementById('box' + i);
    box.textContent = pinDigits[i] !== undefined ? '●' : ' ';
    box.classList.toggle('border-orange-500', i === pinDigits.length);
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
function numClear() { pinDigits.pop(); updateDisplay(); }
document.addEventListener('keydown', e => {
  if (e.key >= '0' && e.key <= '9') numPress(parseInt(e.key));
  else if (e.key === 'Backspace') numClear();
});
updateDisplay();
</script>

<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/staff.php'; ?>
