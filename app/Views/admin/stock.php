<?php $pageTitle = 'Stok'; ob_start(); ?>
<h1 class="text-2xl font-bold mb-6">Stok Yönetimi</h1>

<div class="bg-gray-800 rounded-xl overflow-hidden">
  <table class="w-full text-sm">
    <thead><tr class="text-gray-400 text-left border-b border-gray-700 bg-gray-900">
      <th class="px-4 py-3">Hediye</th>
      <th class="px-4 py-3">Marka</th>
      <th class="px-4 py-3">Başlangıç</th>
      <th class="px-4 py-3">Kalan</th>
      <th class="px-4 py-3">Günlük Limit</th>
      <th class="px-4 py-3">Durum</th>
      <th class="px-4 py-3">Düzenle</th>
    </tr></thead>
    <tbody>
    <?php foreach ($stocks as $s): ?>
      <tr class="border-b border-gray-700/50 hover:bg-gray-700/30">
        <td class="px-4 py-3 font-medium"><?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?></td>
        <td class="px-4 py-3 text-gray-400"><?= htmlspecialchars($s['brand_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
        <td class="px-4 py-3 font-mono"><?= $s['initial_qty'] ?></td>
        <td class="px-4 py-3 font-mono font-bold
          <?= $s['remaining_qty'] == 0 ? 'text-red-400' : ($s['remaining_qty'] <= 3 ? 'text-yellow-400' : 'text-green-400') ?>">
          <?= $s['remaining_qty'] ?>
        </td>
        <td class="px-4 py-3 text-gray-400"><?= $s['daily_limit'] ?? '—' ?></td>
        <td class="px-4 py-3">
          <?php if ($s['remaining_qty'] == 0): ?>
            <span class="px-2 py-0.5 bg-red-900 text-red-300 rounded text-xs">Tükendi</span>
          <?php elseif ($s['remaining_qty'] <= 3): ?>
            <span class="px-2 py-0.5 bg-yellow-900 text-yellow-300 rounded text-xs">Düşük</span>
          <?php else: ?>
            <span class="px-2 py-0.5 bg-green-900 text-green-300 rounded text-xs">OK</span>
          <?php endif; ?>
        </td>
        <td class="px-4 py-3">
          <button onclick="openStockEdit(<?= htmlspecialchars(json_encode($s), ENT_QUOTES, 'UTF-8') ?>)"
                  class="text-xs bg-blue-800 hover:bg-blue-700 text-blue-200 px-2 py-1 rounded">Düzenle</button>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Stock Edit Modal -->
<div id="stockModal" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center z-50">
  <div class="bg-gray-800 rounded-xl p-6 w-full max-w-sm shadow-2xl">
    <h2 class="text-lg font-bold mb-4" id="stockModalTitle">Stok Düzenle</h2>
    <form id="stockForm" method="POST" class="space-y-3">
      <?= \App\Core\Csrf::field() ?>
      <div>
        <label class="block text-sm text-gray-400 mb-1">Başlangıç Stok</label>
        <input type="number" name="initial_qty" id="stockInitial" min="0"
               class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white">
      </div>
      <div>
        <label class="block text-sm text-gray-400 mb-1">Kalan Stok</label>
        <input type="number" name="remaining_qty" id="stockRemaining" min="0"
               class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white">
      </div>
      <div>
        <label class="block text-sm text-gray-400 mb-1">Günlük Limit (boş = limitsiz)</label>
        <input type="number" name="daily_limit" id="stockDaily" min="0"
               class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white">
      </div>
      <div class="flex gap-3 pt-2">
        <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 rounded-lg">Kaydet</button>
        <button type="button" onclick="document.getElementById('stockModal').classList.add('hidden')"
                class="flex-1 bg-gray-700 hover:bg-gray-600 text-white py-2 rounded-lg">İptal</button>
      </div>
    </form>
  </div>
</div>

<script>
function openStockEdit(s) {
  document.getElementById('stockForm').action = '/admin/stock/' + s.prize_id;
  document.getElementById('stockModalTitle').textContent = s.name + ' — Stok';
  document.getElementById('stockInitial').value   = s.initial_qty;
  document.getElementById('stockRemaining').value = s.remaining_qty;
  document.getElementById('stockDaily').value     = s.daily_limit ?? '';
  document.getElementById('stockModal').classList.remove('hidden');
}
</script>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/admin.php'; ?>
