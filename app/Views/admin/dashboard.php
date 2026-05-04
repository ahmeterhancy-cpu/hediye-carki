<?php $pageTitle = 'Dashboard'; ob_start(); ?>
<h1 class="text-2xl font-bold mb-6">Dashboard</h1>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
  <div class="bg-gray-800 rounded-xl p-4 text-center">
    <p class="text-gray-400 text-sm">Bugün</p>
    <p class="text-3xl font-bold text-yellow-400"><?= $today ?></p>
    <p class="text-xs text-gray-500">katılım</p>
  </div>
  <div class="bg-gray-800 rounded-xl p-4 text-center">
    <p class="text-gray-400 text-sm">Toplam</p>
    <p class="text-3xl font-bold text-blue-400"><?= $total ?></p>
    <p class="text-xs text-gray-500">katılım</p>
  </div>
  <div class="bg-gray-800 rounded-xl p-4 text-center">
    <p class="text-gray-400 text-sm">Aktif Kod</p>
    <p class="text-3xl font-bold text-green-400"><?= $activeCodes ?></p>
    <p class="text-xs text-gray-500">bekliyor</p>
  </div>
  <div class="bg-gray-800 rounded-xl p-4 text-center">
    <p class="text-gray-400 text-sm">Düşük Stok</p>
    <p class="text-3xl font-bold text-red-400">
      <?= count(array_filter($stocks, fn($s) => $s['remaining_qty'] <= 3 && $s['remaining_qty'] > 0)) ?>
    </p>
    <p class="text-xs text-gray-500">ürün</p>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
  <!-- Saatlik grafik -->
  <div class="bg-gray-800 rounded-xl p-4">
    <h2 class="font-semibold mb-3">Bugün Saatlik Katılım</h2>
    <canvas id="hourlyChart" height="150"></canvas>
  </div>
  <!-- Hediye dağılımı -->
  <div class="bg-gray-800 rounded-xl p-4">
    <h2 class="font-semibold mb-3">Hediye Dağılımı</h2>
    <canvas id="prizeChart" height="150"></canvas>
  </div>
</div>

<!-- Stok durumu -->
<div class="mt-6 bg-gray-800 rounded-xl p-4">
  <h2 class="font-semibold mb-3">Stok Durumu</h2>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead><tr class="text-gray-400 text-left border-b border-gray-700">
        <th class="pb-2">Hediye</th>
        <th class="pb-2">Marka</th>
        <th class="pb-2 text-right">Kalan</th>
        <th class="pb-2 text-right">Toplam</th>
        <th class="pb-2 text-right">Durum</th>
      </tr></thead>
      <tbody>
      <?php foreach ($stocks as $s): ?>
        <tr class="border-b border-gray-700/50">
          <td class="py-2"><?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?></td>
          <td class="py-2 text-gray-400"><?= htmlspecialchars($s['brand_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
          <td class="py-2 text-right font-mono"><?= $s['remaining_qty'] ?></td>
          <td class="py-2 text-right font-mono text-gray-500"><?= $s['initial_qty'] ?></td>
          <td class="py-2 text-right">
            <?php if ($s['remaining_qty'] == 0): ?>
              <span class="px-2 py-0.5 bg-red-900 text-red-300 rounded text-xs">Tükendi</span>
            <?php elseif ($s['remaining_qty'] <= 3): ?>
              <span class="px-2 py-0.5 bg-yellow-900 text-yellow-300 rounded text-xs">Düşük</span>
            <?php else: ?>
              <span class="px-2 py-0.5 bg-green-900 text-green-300 rounded text-xs">OK</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
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
  data: {
    labels: hours,
    datasets: [{ label: 'Katılım', data: counts, backgroundColor: '#EAB308' }]
  },
  options: {
    plugins: { legend: { display: false } },
    scales: { x: { ticks: { color: '#9CA3AF' } }, y: { ticks: { color: '#9CA3AF' }, beginAtZero: true } }
  }
});

const prizeData = <?= json_encode($prizeDist) ?>;
if (prizeData.length) {
  new Chart(document.getElementById('prizeChart'), {
    type: 'doughnut',
    data: {
      labels: prizeData.map(p => p.name),
      datasets: [{ data: prizeData.map(p => parseInt(p.cnt)), backgroundColor: ['#EAB308','#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#06B6D4','#EC4899'] }]
    },
    options: {
      plugins: { legend: { labels: { color: '#D1D5DB' } } }
    }
  });
}
</script>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/admin.php'; ?>
