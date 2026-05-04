<?php $pageTitle = 'Katılımcılar'; ob_start();
$totalPages = max(1, (int)ceil($total / 50));
?>
<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-bold">Katılımcılar <span class="text-gray-400 text-lg">(<?= $total ?>)</span></h1>
  <a href="/admin/participants/export?<?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY)) ?>"
     class="bg-green-700 hover:bg-green-600 text-white font-bold px-4 py-2 rounded-lg text-sm">
    ↓ Excel
  </a>
</div>

<!-- Filtreler -->
<form method="GET" action="/admin/participants" class="bg-gray-800 rounded-xl p-4 mb-4 flex flex-wrap gap-3">
  <input type="date" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
         class="bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm">
  <input type="date" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
         class="bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm">
  <select name="prize_id" class="bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm">
    <option value="">Tüm Hediyeler</option>
    <?php foreach ($prizes as $pr): ?>
      <option value="<?= $pr['id'] ?>" <?= ($_GET['prize_id'] ?? '') == $pr['id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($pr['name'], ENT_QUOTES, 'UTF-8') ?>
      </option>
    <?php endforeach; ?>
  </select>
  <input type="text" name="phone" placeholder="Telefon ara..." value="<?= htmlspecialchars($_GET['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
         class="bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm">
  <button type="submit" class="bg-blue-700 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">Filtrele</button>
  <a href="/admin/participants" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm">Temizle</a>
</form>

<div class="bg-gray-800 rounded-xl overflow-hidden">
  <table class="w-full text-sm">
    <thead><tr class="text-gray-400 text-left border-b border-gray-700 bg-gray-900">
      <th class="px-4 py-3">#</th>
      <th class="px-4 py-3">Ad Soyad</th>
      <th class="px-4 py-3">Telefon</th>
      <th class="px-4 py-3">Hediye</th>
      <th class="px-4 py-3">Görevli</th>
      <th class="px-4 py-3">Fiş</th>
      <th class="px-4 py-3">Tarih</th>
    </tr></thead>
    <tbody>
    <?php if (empty($rows)): ?>
      <tr><td colspan="7" class="px-4 py-6 text-center text-gray-500">Kayıt bulunamadı.</td></tr>
    <?php else: ?>
      <?php foreach ($rows as $r): ?>
        <tr class="border-b border-gray-700/50 hover:bg-gray-700/30">
          <td class="px-4 py-2 text-gray-500 font-mono text-xs">#<?= $r['id'] ?></td>
          <td class="px-4 py-2">
            <?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name'], ENT_QUOTES, 'UTF-8') ?>
          </td>
          <td class="px-4 py-2 font-mono text-xs"><?= htmlspecialchars($r['phone'], ENT_QUOTES, 'UTF-8') ?></td>
          <td class="px-4 py-2"><?= htmlspecialchars($r['prize_name_snapshot'], ENT_QUOTES, 'UTF-8') ?></td>
          <td class="px-4 py-2 text-gray-400"><?= htmlspecialchars($r['staff_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
          <td class="px-4 py-2 text-gray-400 text-xs">
            <?= $r['receipt_no'] ? htmlspecialchars($r['receipt_no'], ENT_QUOTES, 'UTF-8') : '—' ?>
            <?= $r['receipt_amount'] ? ' / ' . number_format($r['receipt_amount'], 2) . '₺' : '' ?>
          </td>
          <td class="px-4 py-2 text-xs text-gray-400"><?= $r['created_at'] ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Sayfalama -->
<?php if ($totalPages > 1): ?>
  <div class="mt-4 flex gap-2 justify-center">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <?php $q = array_merge($_GET, ['page' => $i]); ?>
      <a href="/admin/participants?<?= http_build_query($q) ?>"
         class="px-3 py-1 rounded <?= $page == $i ? 'bg-yellow-500 text-gray-900 font-bold' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' ?> text-sm">
        <?= $i ?>
      </a>
    <?php endfor; ?>
  </div>
<?php endif; ?>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/admin.php'; ?>
