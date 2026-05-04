<?php $pageTitle = 'Görevliler'; ob_start(); ?>
<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-bold">Görevli Yönetimi</h1>
  <button onclick="document.getElementById('addStaffModal').classList.remove('hidden')"
          class="bg-yellow-500 hover:bg-yellow-400 text-gray-900 font-bold px-4 py-2 rounded-lg text-sm">
    + Yeni Görevli
  </button>
</div>

<div class="bg-gray-800 rounded-xl overflow-hidden">
  <table class="w-full text-sm">
    <thead><tr class="text-gray-400 text-left border-b border-gray-700 bg-gray-900">
      <th class="px-4 py-3">Ad</th>
      <th class="px-4 py-3">Ekleyen</th>
      <th class="px-4 py-3">Son Giriş</th>
      <th class="px-4 py-3">Durum</th>
      <th class="px-4 py-3">İşlem</th>
    </tr></thead>
    <tbody>
    <?php foreach ($staff as $s): ?>
      <tr class="border-b border-gray-700/50 hover:bg-gray-700/30">
        <td class="px-4 py-3 font-medium"><?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?></td>
        <td class="px-4 py-3 text-gray-400"><?= htmlspecialchars($s['created_by_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
        <td class="px-4 py-3 text-xs text-gray-400"><?= $s['last_login_at'] ?? '—' ?></td>
        <td class="px-4 py-3">
          <?php if ($s['is_active']): ?>
            <span class="px-2 py-0.5 bg-green-900 text-green-300 rounded text-xs">Aktif</span>
          <?php else: ?>
            <span class="px-2 py-0.5 bg-gray-700 text-gray-400 rounded text-xs">Pasif</span>
          <?php endif; ?>
        </td>
        <td class="px-4 py-3 flex gap-2">
          <button onclick="openResetPin(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['name']), ENT_QUOTES, 'UTF-8') ?>')"
                  class="text-xs bg-blue-800 hover:bg-blue-700 text-blue-200 px-2 py-1 rounded">PIN Sıfırla</button>
          <form method="POST" action="/admin/staff-users/<?= $s['id'] ?>/toggle">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="text-xs bg-yellow-800 hover:bg-yellow-700 text-yellow-200 px-2 py-1 rounded">
              <?= $s['is_active'] ? 'Pasifleştir' : 'Aktifleştir' ?>
            </button>
          </form>
          <form method="POST" action="/admin/staff-users/<?= $s['id'] ?>/delete"
                onsubmit="return confirm('Görevli silinsin mi?')">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="text-xs bg-red-900 hover:bg-red-800 text-red-300 px-2 py-1 rounded">Sil</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($staff)): ?>
      <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Henüz görevli yok.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Add Staff Modal -->
<div id="addStaffModal" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center z-50">
  <div class="bg-gray-800 rounded-xl p-6 w-full max-w-sm shadow-2xl">
    <h2 class="text-lg font-bold mb-4">Yeni Görevli</h2>
    <form method="POST" action="/admin/staff-users" class="space-y-3">
      <?= \App\Core\Csrf::field() ?>
      <div>
        <label class="block text-sm text-gray-400 mb-1">Ad Soyad *</label>
        <input type="text" name="name" required
               class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white">
      </div>
      <div>
        <label class="block text-sm text-gray-400 mb-1">PIN (6 hane)</label>
        <div class="flex gap-2">
          <input type="text" name="pin" id="newPin" pattern="\d{6}" maxlength="6" placeholder="6 rakam"
                 class="flex-1 bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white font-mono tracking-widest text-xl">
          <button type="button" onclick="autoGeneratePin()" id="genBtn"
                  class="bg-blue-700 hover:bg-blue-600 text-white px-3 py-2 rounded-lg text-sm">Üret</button>
        </div>
      </div>
      <div class="flex gap-3 pt-2">
        <button type="submit" class="flex-1 bg-yellow-500 hover:bg-yellow-400 text-gray-900 font-bold py-2 rounded-lg">Ekle</button>
        <button type="button" onclick="document.getElementById('addStaffModal').classList.add('hidden')"
                class="flex-1 bg-gray-700 hover:bg-gray-600 text-white py-2 rounded-lg">İptal</button>
      </div>
    </form>
  </div>
</div>

<!-- Reset PIN Modal -->
<div id="resetPinModal" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center z-50">
  <div class="bg-gray-800 rounded-xl p-6 w-full max-w-sm shadow-2xl">
    <h2 class="text-lg font-bold mb-1">PIN Sıfırla</h2>
    <p id="resetPinName" class="text-gray-400 text-sm mb-4"></p>
    <form id="resetPinForm" method="POST" class="space-y-3">
      <?= \App\Core\Csrf::field() ?>
      <div>
        <label class="block text-sm text-gray-400 mb-1">Yeni PIN (6 hane)</label>
        <div class="flex gap-2">
          <input type="text" name="pin" id="resetPin" pattern="\d{6}" maxlength="6"
                 class="flex-1 bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white font-mono tracking-widest text-xl">
          <button type="button" onclick="autoGenerateResetPin()"
                  class="bg-blue-700 hover:bg-blue-600 text-white px-3 py-2 rounded-lg text-sm">Üret</button>
        </div>
      </div>
      <div class="flex gap-3 pt-2">
        <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 rounded-lg">Kaydet</button>
        <button type="button" onclick="document.getElementById('resetPinModal').classList.add('hidden')"
                class="flex-1 bg-gray-700 hover:bg-gray-600 text-white py-2 rounded-lg">İptal</button>
      </div>
    </form>
  </div>
</div>

<script>
async function autoGeneratePin() {
  const btn = document.getElementById('genBtn');
  btn.disabled = true; btn.textContent = '...';
  try {
    const r = await fetch('/admin/staff-users/generate-pin');
    const j = await r.json();
    if (j.ok) document.getElementById('newPin').value = j.pin;
    else alert('PIN üretilemedi.');
  } finally {
    btn.disabled = false; btn.textContent = 'Üret';
  }
}

async function autoGenerateResetPin() {
  const r = await fetch('/admin/staff-users/generate-pin');
  const j = await r.json();
  if (j.ok) document.getElementById('resetPin').value = j.pin;
}

function openResetPin(id, name) {
  document.getElementById('resetPinForm').action = '/admin/staff-users/' + id + '/reset-pin';
  document.getElementById('resetPinName').textContent = name;
  document.getElementById('resetPin').value = '';
  document.getElementById('resetPinModal').classList.remove('hidden');
}
</script>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/admin.php'; ?>
