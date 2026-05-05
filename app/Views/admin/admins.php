<?php $pageTitle = 'Adminler'; ob_start(); ?>
<div class="flex items-center justify-between mb-6">
  <div>
    <h1 class="text-2xl font-bold">Admin Yönetimi</h1>
    <p class="text-sm text-gray-400 mt-1">Admin kullanıcılarını ekleyin, parolalarını değiştirin.</p>
  </div>
  <button onclick="document.getElementById('addAdminModal').classList.remove('hidden')"
          class="bg-yellow-500 hover:bg-yellow-400 text-gray-900 font-bold px-4 py-2 rounded-lg text-sm">
    + Yeni Admin
  </button>
</div>

<div class="bg-gray-800 rounded-xl overflow-hidden">
  <table class="w-full text-sm">
    <thead><tr class="text-gray-400 text-left border-b border-gray-700 bg-gray-900">
      <th class="px-4 py-3">Ad</th>
      <th class="px-4 py-3">E-posta</th>
      <th class="px-4 py-3">Ekleyen</th>
      <th class="px-4 py-3">Son Giriş</th>
      <th class="px-4 py-3">Durum</th>
      <th class="px-4 py-3">İşlem</th>
    </tr></thead>
    <tbody>
    <?php foreach ($admins as $a): $isMe = ((int)$a['id'] === $currentId); ?>
      <tr class="border-b border-gray-700/50 hover:bg-gray-700/30 <?= $isMe ? 'bg-blue-900/20' : '' ?>">
        <td class="px-4 py-3 font-medium">
          <?= htmlspecialchars($a['name'], ENT_QUOTES, 'UTF-8') ?>
          <?php if ($isMe): ?><span class="text-xs text-blue-300 ml-2">(siz)</span><?php endif; ?>
        </td>
        <td class="px-4 py-3 text-gray-300 font-mono text-xs"><?= htmlspecialchars($a['email'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
        <td class="px-4 py-3 text-gray-400"><?= htmlspecialchars($a['created_by_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
        <td class="px-4 py-3 text-xs text-gray-400"><?= $a['last_login_at'] ?? '—' ?></td>
        <td class="px-4 py-3">
          <?php if ($a['is_active']): ?>
            <span class="px-2 py-0.5 bg-green-900 text-green-300 rounded text-xs">Aktif</span>
          <?php else: ?>
            <span class="px-2 py-0.5 bg-gray-700 text-gray-400 rounded text-xs">Pasif</span>
          <?php endif; ?>
        </td>
        <td class="px-4 py-3 flex flex-wrap gap-2">
          <button onclick='openEdit(<?= json_encode(["id"=>(int)$a["id"], "name"=>$a["name"], "email"=>$a["email"]], JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'
                  class="text-xs bg-gray-700 hover:bg-gray-600 text-gray-200 px-2 py-1 rounded">Düzenle</button>
          <button onclick='openPassword(<?= (int)$a["id"] ?>, <?= json_encode($a["name"], JSON_HEX_APOS|JSON_HEX_QUOT) ?>, <?= $isMe ? 'true' : 'false' ?>)'
                  class="text-xs bg-blue-800 hover:bg-blue-700 text-blue-200 px-2 py-1 rounded">Parola</button>
          <?php if (!$isMe): ?>
            <form method="POST" action="/admin/admins/<?= $a['id'] ?>/toggle" class="inline">
              <?= \App\Core\Csrf::field() ?>
              <button type="submit" class="text-xs bg-yellow-800 hover:bg-yellow-700 text-yellow-200 px-2 py-1 rounded">
                <?= $a['is_active'] ? 'Pasifleştir' : 'Aktifleştir' ?>
              </button>
            </form>
            <form method="POST" action="/admin/admins/<?= $a['id'] ?>/delete" class="inline"
                  onsubmit="return confirm('Bu admin silinsin mi? Bu işlem geri alınamaz.')">
              <?= \App\Core\Csrf::field() ?>
              <button type="submit" class="text-xs bg-red-900 hover:bg-red-800 text-red-300 px-2 py-1 rounded">Sil</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Add Admin Modal -->
<div id="addAdminModal" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center z-50">
  <div class="bg-gray-800 rounded-xl p-6 w-full max-w-md shadow-2xl">
    <h2 class="text-lg font-bold mb-4">Yeni Admin</h2>
    <form method="POST" action="/admin/admins" class="space-y-3">
      <?= \App\Core\Csrf::field() ?>
      <div>
        <label class="block text-sm text-gray-400 mb-1">Ad Soyad *</label>
        <input type="text" name="name" required
               class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white">
      </div>
      <div>
        <label class="block text-sm text-gray-400 mb-1">E-posta *</label>
        <input type="email" name="email" required
               class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white">
      </div>
      <div>
        <label class="block text-sm text-gray-400 mb-1">Parola (en az 8 karakter) *</label>
        <input type="password" name="password" required minlength="8"
               class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white">
      </div>
      <div class="flex gap-3 pt-2">
        <button type="submit" class="flex-1 bg-yellow-500 hover:bg-yellow-400 text-gray-900 font-bold py-2 rounded-lg">Ekle</button>
        <button type="button" onclick="document.getElementById('addAdminModal').classList.add('hidden')"
                class="flex-1 bg-gray-700 hover:bg-gray-600 text-white py-2 rounded-lg">İptal</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Admin Modal -->
<div id="editAdminModal" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center z-50">
  <div class="bg-gray-800 rounded-xl p-6 w-full max-w-md shadow-2xl">
    <h2 class="text-lg font-bold mb-4">Admin Düzenle</h2>
    <form id="editAdminForm" method="POST" class="space-y-3">
      <?= \App\Core\Csrf::field() ?>
      <div>
        <label class="block text-sm text-gray-400 mb-1">Ad Soyad *</label>
        <input type="text" name="name" id="editName" required
               class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white">
      </div>
      <div>
        <label class="block text-sm text-gray-400 mb-1">E-posta *</label>
        <input type="email" name="email" id="editEmail" required
               class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white">
      </div>
      <div class="flex gap-3 pt-2">
        <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 rounded-lg">Kaydet</button>
        <button type="button" onclick="document.getElementById('editAdminModal').classList.add('hidden')"
                class="flex-1 bg-gray-700 hover:bg-gray-600 text-white py-2 rounded-lg">İptal</button>
      </div>
    </form>
  </div>
</div>

<!-- Password Modal -->
<div id="passwordModal" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center z-50">
  <div class="bg-gray-800 rounded-xl p-6 w-full max-w-md shadow-2xl">
    <h2 class="text-lg font-bold mb-1">Parola Değiştir</h2>
    <p id="passwordTarget" class="text-gray-400 text-sm mb-4"></p>
    <form id="passwordForm" method="POST" class="space-y-3">
      <?= \App\Core\Csrf::field() ?>
      <div id="currentPasswordWrap" class="hidden">
        <label class="block text-sm text-gray-400 mb-1">Mevcut Parolanız *</label>
        <input type="password" name="current_password" id="currentPassword"
               class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white">
      </div>
      <div>
        <label class="block text-sm text-gray-400 mb-1">Yeni Parola (en az 8 karakter) *</label>
        <input type="password" name="new_password" required minlength="8"
               class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white">
      </div>
      <div>
        <label class="block text-sm text-gray-400 mb-1">Yeni Parola (Tekrar) *</label>
        <input type="password" name="confirm_password" required minlength="8"
               class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white">
      </div>
      <div class="flex gap-3 pt-2">
        <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 rounded-lg">Güncelle</button>
        <button type="button" onclick="document.getElementById('passwordModal').classList.add('hidden')"
                class="flex-1 bg-gray-700 hover:bg-gray-600 text-white py-2 rounded-lg">İptal</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEdit(data) {
  document.getElementById('editAdminForm').action = '/admin/admins/' + data.id;
  document.getElementById('editName').value  = data.name  || '';
  document.getElementById('editEmail').value = data.email || '';
  document.getElementById('editAdminModal').classList.remove('hidden');
}

function openPassword(id, name, isSelf) {
  document.getElementById('passwordForm').action = '/admin/admins/' + id + '/password';
  document.getElementById('passwordTarget').textContent = name + (isSelf ? ' (kendiniz)' : '');
  document.getElementById('currentPasswordWrap').classList.toggle('hidden', !isSelf);
  document.getElementById('currentPassword').required = !!isSelf;
  document.getElementById('passwordForm').reset();
  document.getElementById('passwordModal').classList.remove('hidden');
}
</script>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/admin.php'; ?>
