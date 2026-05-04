<?php $pageTitle = 'Raporlar'; ob_start(); ?>
<h1 class="text-2xl font-bold mb-6">Raporlar</h1>

<div class="grid grid-cols-2 gap-4 mb-8">
  <div class="bg-gray-800 rounded-xl p-4 text-center">
    <p class="text-gray-400 text-sm">Bugün</p>
    <p class="text-4xl font-bold text-yellow-400"><?= $today ?></p>
  </div>
  <div class="bg-gray-800 rounded-xl p-4 text-center">
    <p class="text-gray-400 text-sm">Toplam</p>
    <p class="text-4xl font-bold text-blue-400"><?= $total ?></p>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
  <div class="bg-gray-800 rounded-xl p-4">
    <h2 class="font-semibold mb-3">Saatlik Katılım (Bugün)</h2>
    <canvas id="hourlyChart" height="200"></canvas>
  </div>
  <div class="bg-gray-800 rounded-xl p-4">
    <h2 class="font-semibold mb-3">Hediye Dağılımı</h2>
    <canvas id="prizeChart" height="200"></canvas>
    <div class="mt-3 space-y-1">
      <?php foreach (array_slice($prizeDist, 0, 5) as $p): ?>
        <div class="flex justify-between text-sm">
          <span class="text-gray-300"><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></span>
          <span class="text-yellow-400 font-mono"><?= $p['cnt'] ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const hourlyData = <?= json_encode($hourly) ?>;
const hours = Array.from({length: 24}, (_, i) => i + ':00');
const counts = Array.from({length: 24}, (_, i) => {
  const found = hourlyData.find(h => parseInt(h.hour) === i);
  return found ? parseInt(found.cnt) : 0;
});

new Chart(document.getElementById('hourlyChart'), {
  type: 'bar',
  data: { labels: hours, datasets: [{ label: 'Katılım', data: counts, backgroundColor: '#EAB308' }] },
  options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: '#9CA3AF' } }, y: { ticks: { color: '#9CA3AF' }, beginAtZero: true } } }
});

const pd = <?= json_encode($prizeDist) ?>;
if (pd.length) {
  new Chart(document.getElementById('prizeChart'), {
    type: 'doughnut',
    data: { labels: pd.map(p => p.name), datasets: [{ data: pd.map(p => parseInt(p.cnt)), backgroundColor: ['#EAB308','#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#06B6D4','#EC4899'] }] },
    options: { plugins: { legend: { display: false } } }
  });
}
</script>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/admin.php'; ?>
