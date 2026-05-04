<?php $pageTitle = 'Dilimler'; ob_start(); ?>
<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-bold">Çark Dilimleri</h1>
  <button onclick="document.getElementById('addModal').classList.remove('hidden')"
          class="bg-yellow-500 hover:bg-yellow-400 text-gray-900 font-bold px-4 py-2 rounded-lg text-sm">
    + Yeni Dilim
  </button>
</div>

<div class="bg-gray-800 rounded-xl overflow-hidden">
  <table class="w-full text-sm" id="prizeTable">
    <thead><tr class="text-gray-400 text-left border-b border-gray-700 bg-gray-900">
      <th class="px-4 py-3">Sıra</th>
      <th class="px-4 py-3">Renk</th>
      <th class="px-4 py-3">Ad</th>
      <th class="px-4 py-3">Marka</th>
      <th class="px-4 py-3">Alış Noktası</th>
      <th class="px-4 py-3">Ağırlık</th>
      <th class="px-4 py-3">Stok</th>
      <th class="px-4 py-3">Durum</th>
      <th class="px-4 py-3">İşlem</th>
    </tr></thead>
    <tbody id="sortableBody">
    <?php foreach ($prizes as $p): ?>
      <tr class="border-b border-gray-700/50 hover:bg-gray-700/30 cursor-move" data-id="<?= $p['id'] ?>">
        <td class="px-4 py-3 text-gray-500 select-none">⠿</td>
        <td class="px-4 py-3">
          <span class="inline-block w-6 h-6 rounded" style="background:<?= htmlspecialchars($p['color_hex'], ENT_QUOTES, 'UTF-8') ?>"></span>
        </td>
        <td class="px-4 py-3 font-medium"><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></td>
        <td class="px-4 py-3 text-gray-400"><?= htmlspecialchars($p['brand_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
        <td class="px-4 py-3 text-gray-400 text-xs max-w-[180px] truncate" title="<?= htmlspecialchars($p['pickup_location'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <?= $p['pickup_location'] ? htmlspecialchars($p['pickup_location'], ENT_QUOTES, 'UTF-8') : '<span class="text-gray-600">— (otomatik)</span>' ?>
        </td>
        <td class="px-4 py-3">
          <span class="px-2 py-0.5 bg-blue-900 text-blue-300 rounded text-xs"><?= $p['weight'] ?></span>
        </td>
        <td class="px-4 py-3 font-mono">
          <?php if (($p['remaining_qty'] ?? null) !== null): ?>
            <span class="<?= $p['remaining_qty'] == 0 ? 'text-red-400' : ($p['remaining_qty'] <= 3 ? 'text-yellow-400' : 'text-green-400') ?>">
              <?= $p['remaining_qty'] ?>/<?= $p['initial_qty'] ?>
            </span>
          <?php else: ?>
            <span class="text-gray-500">—</span>
          <?php endif; ?>
        </td>
        <td class="px-4 py-3">
          <?php if ($p['is_active']): ?>
            <span class="px-2 py-0.5 bg-green-900 text-green-300 rounded text-xs">Aktif</span>
          <?php else: ?>
            <span class="px-2 py-0.5 bg-gray-700 text-gray-400 rounded text-xs">Pasif</span>
          <?php endif; ?>
        </td>
        <td class="px-4 py-3 flex gap-2">
          <button onclick="openEdit(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>)"
                  class="text-xs bg-blue-800 hover:bg-blue-700 text-blue-200 px-2 py-1 rounded">Düzenle</button>
          <form method="POST" action="/admin/prizes/<?= $p['id'] ?>/delete" onsubmit="return confirm('Silmek istediğinize emin misiniz?')">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="text-xs bg-red-900 hover:bg-red-800 text-red-300 px-2 py-1 rounded">Sil</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Add Modal -->
<div id="addModal" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center z-50">
  <div class="bg-gray-800 rounded-xl p-6 w-full max-w-md shadow-2xl">
    <h2 class="text-lg font-bold mb-4">Yeni Dilim</h2>
    <form method="POST" action="/admin/prizes" enctype="multipart/form-data" class="space-y-3">
      <?= \App\Core\Csrf::field() ?>
      <input type="text" name="name" placeholder="Hediye Adı *" required
             class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm">
      <input type="text" name="brand_name" placeholder="Marka Adı"
             class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm">
      <div>
        <label class="text-xs text-gray-400">Hediye Alış Noktası</label>
        <input type="text" name="pickup_location" placeholder="örn: Üst kat Starbucks standı"
               class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm">
        <p class="text-xs text-gray-500 mt-1">Boş bırakırsan "{Marka} standı" gösterilir.</p>
      </div>
      <div class="flex gap-3">
        <div class="flex-1">
          <label class="text-xs text-gray-400">Renk</label>
          <input type="color" name="color_hex" value="#FFB400"
                 class="w-full h-10 rounded-lg border border-gray-600 bg-gray-700 cursor-pointer">
        </div>
        <div class="flex-1">
          <label class="text-xs text-gray-400">Ağırlık (1–100)</label>
          <input type="number" name="weight" value="10" min="1" max="100"
                 class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm">
        </div>
      </div>
      <div>
        <label class="text-xs text-gray-400">Başlangıç Stok</label>
        <input type="number" name="initial_qty" value="50" min="0"
               class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm">
      </div>
      <div>
        <label class="text-xs text-gray-400">Logo (opsiyonel, maks 2MB)</label>
        <input type="file" name="logo" accept="image/*"
               class="w-full text-sm text-gray-400">
      </div>
      <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" checked class="rounded"> Aktif
      </label>
      <div class="flex gap-3 pt-2">
        <button type="submit" class="flex-1 bg-yellow-500 hover:bg-yellow-400 text-gray-900 font-bold py-2 rounded-lg">Ekle</button>
        <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')"
                class="flex-1 bg-gray-700 hover:bg-gray-600 text-white py-2 rounded-lg">İptal</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center z-50">
  <div class="bg-gray-800 rounded-xl p-6 w-full max-w-md shadow-2xl">
    <h2 class="text-lg font-bold mb-4">Dilim Düzenle</h2>
    <form id="editForm" method="POST" enctype="multipart/form-data" class="space-y-3">
      <?= \App\Core\Csrf::field() ?>
      <input type="text" name="name" id="editName" placeholder="Hediye Adı *" required
             class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm">
      <input type="text" name="brand_name" id="editBrand" placeholder="Marka Adı"
             class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm">
      <div>
        <label class="text-xs text-gray-400">Hediye Alış Noktası</label>
        <input type="text" name="pickup_location" id="editPickup" placeholder="örn: Üst kat Starbucks standı"
               class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm">
      </div>
      <div class="flex gap-3">
        <div class="flex-1">
          <label class="text-xs text-gray-400">Renk</label>
          <input type="color" name="color_hex" id="editColor"
                 class="w-full h-10 rounded-lg border border-gray-600 bg-gray-700 cursor-pointer">
        </div>
        <div class="flex-1">
          <label class="text-xs text-gray-400">Ağırlık</label>
          <input type="number" name="weight" id="editWeight" min="1" max="100"
                 class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm">
        </div>
      </div>
      <div>
        <label class="text-xs text-gray-400">Logo (değiştirmek için yükleyin)</label>
        <input type="file" name="logo" accept="image/*" class="w-full text-sm text-gray-400">
      </div>
      <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" id="editActive" class="rounded"> Aktif
      </label>
      <div class="flex gap-3 pt-2">
        <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 rounded-lg">Kaydet</button>
        <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')"
                class="flex-1 bg-gray-700 hover:bg-gray-600 text-white py-2 rounded-lg">İptal</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
function openEdit(p) {
  document.getElementById('editForm').action  = '/admin/prizes/' + p.id;
  document.getElementById('editName').value   = p.name;
  document.getElementById('editBrand').value  = p.brand_name || '';
  document.getElementById('editPickup').value = p.pickup_location || '';
  document.getElementById('editColor').value  = p.color_hex;
  document.getElementById('editWeight').value = p.weight;
  document.getElementById('editActive').checked = p.is_active == 1;
  document.getElementById('editModal').classList.remove('hidden');
}

const sortable = Sortable.create(document.getElementById('sortableBody'), {
  animation: 150,
  onEnd() {
    const ids = [...document.querySelectorAll('#sortableBody tr')].map(r => r.dataset.id);
    fetch('/admin/prizes/reorder', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': '<?= \App\Core\Csrf::token() ?>' },
      body: '_csrf=<?= \App\Core\Csrf::token() ?>&order=' + encodeURIComponent(JSON.stringify(ids))
    });
  }
});
</script>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/admin.php'; ?>
