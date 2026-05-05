<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\RateLimit;
use App\Core\Response;
use App\Models\AuditLog;
use App\Models\Participant;
use App\Models\Prize;
use App\Models\Settings;
use App\Models\SpinCode;
use App\Models\Stock;
use App\Models\User;
use App\Services\ReportService;

class AdminController
{
    private function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    // ── Login ──────────────────────────────────────────────────────────────

    public function loginForm(): void
    {
        if (Auth::adminId()) Response::redirect('/admin');
        require __DIR__ . '/../Views/admin/login.php';
    }

    public function loginPost(): void
    {
        Csrf::check();
        RateLimit::check('admin_login:' . $this->ip(), 5, 900);

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = User::findByEmail($email);
        if ($user && password_verify($password, $user['password_hash'])) {
            RateLimit::reset('admin_login:' . $this->ip());
            Auth::adminLogin($user['id'], $user['name']);
            User::touchLastLogin($user['id']);
            AuditLog::write($user['id'], 'admin.login', null, null, [], $this->ip());
            Response::redirect('/admin');
        }

        AuditLog::write(null, 'admin.login_failed', null, null, ['email' => $email], $this->ip());
        $_SESSION['flash_error'] = 'E-posta veya parola hatalı.';
        Response::redirect('/admin/login');
    }

    public function logout(): void
    {
        $id = Auth::adminId();
        Auth::adminLogout();
        AuditLog::write($id, 'admin.logout', null, null, [], $this->ip());
        Response::redirect('/admin/login');
    }

    // ── Dashboard ─────────────────────────────────────────────────────────

    public function dashboard(): void
    {
        Auth::adminCheck();
        $today      = Participant::todayCount();
        $total      = Participant::totalCount();
        $hourly     = Participant::hourlyToday();
        $prizeDist  = Participant::prizeDistribution();
        $stocks     = Stock::status();
        $activeCodes = SpinCode::activeCount();
        require __DIR__ . '/../Views/admin/dashboard.php';
    }

    // ── Prizes ────────────────────────────────────────────────────────────

    public function prizes(): void
    {
        Auth::adminCheck();
        $prizes = Prize::all();
        require __DIR__ . '/../Views/admin/prizes.php';
    }

    public function prizeCreate(): void
    {
        Auth::adminCheck();
        Csrf::check();

        $logo = $this->handleLogoUpload();

        $id = Prize::create([
            'name'            => trim($_POST['name'] ?? ''),
            'brand_name'      => trim($_POST['brand_name'] ?? ''),
            'pickup_location' => trim($_POST['pickup_location'] ?? ''),
            'logo_path'       => $logo,
            'color_hex'       => $_POST['color_hex'] ?? '#FFB400',
            'weight'          => (int)($_POST['weight'] ?? 10),
            'display_order'   => (int)($_POST['display_order'] ?? 0),
            'is_active'       => isset($_POST['is_active']) ? 1 : 0,
        ]);

        Stock::upsert($id, (int)($_POST['initial_qty'] ?? 0), (int)($_POST['initial_qty'] ?? 0));
        AuditLog::write(Auth::adminId(), 'prize.created', 'prize', $id, ['name' => $_POST['name'] ?? ''], $this->ip());
        Response::redirect('/admin/prizes');
    }

    public function prizeUpdate(string $id): void
    {
        Auth::adminCheck();
        Csrf::check();

        $prize = Prize::find((int)$id);
        if (!$prize) Response::abort(404);

        $logo = $this->handleLogoUpload($prize['logo_path'] ?? null);

        Prize::update((int)$id, [
            'name'            => trim($_POST['name'] ?? ''),
            'brand_name'      => trim($_POST['brand_name'] ?? ''),
            'pickup_location' => trim($_POST['pickup_location'] ?? ''),
            'logo_path'       => $logo,
            'color_hex'       => $_POST['color_hex'] ?? '#FFB400',
            'weight'          => (int)($_POST['weight'] ?? 10),
            'display_order'   => (int)($_POST['display_order'] ?? 0),
            'is_active'       => isset($_POST['is_active']) ? 1 : 0,
        ]);

        AuditLog::write(Auth::adminId(), 'prize.updated', 'prize', (int)$id, [], $this->ip());
        Response::redirect('/admin/prizes');
    }

    public function prizeDelete(string $id): void
    {
        Auth::adminCheck();
        Csrf::check();
        Prize::delete((int)$id);
        AuditLog::write(Auth::adminId(), 'prize.deleted', 'prize', (int)$id, [], $this->ip());
        Response::redirect('/admin/prizes');
    }

    public function prizeReorder(): void
    {
        Auth::adminCheck();
        Csrf::check();
        $ids = json_decode($_POST['order'] ?? '[]', true);
        if (is_array($ids)) Prize::reorder($ids);
        Response::json(['ok' => true]);
    }

    // ── Stock ─────────────────────────────────────────────────────────────

    public function stock(): void
    {
        Auth::adminCheck();
        $stocks = Stock::status();
        require __DIR__ . '/../Views/admin/stock.php';
    }

    public function stockUpdate(string $prizeId): void
    {
        Auth::adminCheck();
        Csrf::check();

        $initial   = (int)($_POST['initial_qty'] ?? 0);
        $remaining = (int)($_POST['remaining_qty'] ?? 0);
        $daily     = isset($_POST['daily_limit']) && $_POST['daily_limit'] !== '' ? (int)$_POST['daily_limit'] : null;

        Stock::upsert((int)$prizeId, $initial, $remaining, $daily);
        AuditLog::write(Auth::adminId(), 'stock.updated', 'prize', (int)$prizeId, ['remaining' => $remaining], $this->ip());
        Response::redirect('/admin/stock');
    }

    // ── Settings ──────────────────────────────────────────────────────────

    public function settings(): void
    {
        Auth::adminCheck();
        $settings = Settings::all();
        require __DIR__ . '/../Views/admin/settings.php';
    }

    public function settingsPost(): void
    {
        Auth::adminCheck();
        Csrf::check();

        $allowed = [
            'min_spend', 'start_time', 'end_time',
            'per_phone_limit', 'per_phone_window',
            'event_active', 'event_title', 'code_ttl_seconds',
            'company_name',
        ];
        $data = [];
        foreach ($allowed as $key) {
            if (isset($_POST[$key])) $data[$key] = $_POST[$key];
        }
        $data['event_active'] = isset($_POST['event_active']) ? '1' : '0';

        // Logo upload
        if (!empty($_FILES['company_logo']['tmp_name'])) {
            $path = $this->uploadBrandingFile('company_logo', 5);
            if ($path) $data['company_logo_path'] = $path;
        } elseif (!empty($_POST['remove_company_logo'])) {
            $data['company_logo_path'] = '';
        }

        // Background upload
        if (!empty($_FILES['bg_image']['tmp_name'])) {
            $path = $this->uploadBrandingFile('bg_image', 8);
            if ($path) $data['bg_image_path'] = $path;
        } elseif (!empty($_POST['reset_bg'])) {
            $data['bg_image_path'] = '/img/bg-mall.jpg';
        }

        Settings::setMany($data);

        AuditLog::write(Auth::adminId(), 'settings.updated', null, null,
            array_keys($data), $this->ip());
        Response::redirect('/admin/settings');
    }

    private function uploadBrandingFile(string $field, int $maxMb): ?string
    {
        $file    = $_FILES[$field] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errCode = $file['error'] ?? -1;
            $msgMap = [
                UPLOAD_ERR_INI_SIZE   => 'Dosya PHP upload_max_filesize sınırını aştı.',
                UPLOAD_ERR_FORM_SIZE  => 'Dosya form sınırını aştı.',
                UPLOAD_ERR_PARTIAL    => 'Dosya kısmen yüklendi.',
                UPLOAD_ERR_NO_FILE    => 'Dosya seçilmedi.',
                UPLOAD_ERR_NO_TMP_DIR => 'Geçici dizin yok.',
                UPLOAD_ERR_CANT_WRITE => 'Diske yazılamadı.',
                UPLOAD_ERR_EXTENSION  => 'Bir uzantı upload\'ı durdurdu.',
            ];
            $_SESSION['flash_error'] = 'Yükleme hatası: ' . ($msgMap[$errCode] ?? "Kod {$errCode}");
            return null;
        }

        $maxSize = $maxMb * 1024 * 1024;
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $extMap  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif',
                    'image/webp' => 'webp', 'image/svg+xml' => 'svg'];

        if ($file['size'] > $maxSize) {
            $_SESSION['flash_error'] = "Dosya {$maxMb}MB'den büyük olamaz (boyut: " . round($file['size']/1024/1024, 1) . "MB).";
            return null;
        }

        // mime_content_type yoksa finfo veya uzantıdan çöz
        $mime = null;
        if (function_exists('mime_content_type')) {
            $mime = mime_content_type($file['tmp_name']);
        } elseif (function_exists('finfo_open')) {
            $f = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($f, $file['tmp_name']);
            finfo_close($f);
        } else {
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $mime = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png',
                     'gif'=>'image/gif','webp'=>'image/webp','svg'=>'image/svg+xml'][$ext] ?? null;
        }

        if (!in_array($mime, $allowed, true)) {
            $_SESSION['flash_error'] = 'Geçersiz dosya formatı (' . ($mime ?? 'bilinmeyen') . ').';
            return null;
        }
        $ext      = $extMap[$mime];
        $filename = $field . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $destDir  = __DIR__ . '/../../uploads';

        if (!is_dir($destDir)) {
            if (!@mkdir($destDir, 0775, true) && !is_dir($destDir)) {
                $_SESSION['flash_error'] = 'uploads/ klasörü oluşturulamadı (yazma izni yok): ' . $destDir;
                return null;
            }
        }
        if (!is_writable($destDir)) {
            @chmod($destDir, 0775);
            if (!is_writable($destDir)) {
                $_SESSION['flash_error'] = 'uploads/ klasörüne yazma izni yok. cPanel\'de chmod 775 yapın: ' . $destDir;
                return null;
            }
        }

        if (!move_uploaded_file($file['tmp_name'], $destDir . '/' . $filename)) {
            $_SESSION['flash_error'] = 'Dosya taşınamadı: ' . $destDir . '/' . $filename;
            return null;
        }
        return '/uploads/' . $filename;
    }

    // ── Participants ──────────────────────────────────────────────────────

    public function participants(): void
    {
        Auth::adminCheck();
        $filters  = $_GET;
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $rows     = Participant::filter($filters, $page);
        $total    = Participant::countFilter($filters);
        $prizes   = Prize::all();
        require __DIR__ . '/../Views/admin/participants.php';
    }

    public function participantsExport(): void
    {
        Auth::adminCheck();
        $path = (new ReportService())->exportParticipants($_GET);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        unlink($path);
        exit;
    }

    // ── Reports ───────────────────────────────────────────────────────────

    public function reports(): void
    {
        Auth::adminCheck();
        $today     = Participant::todayCount();
        $total     = Participant::totalCount();
        $hourly    = Participant::hourlyToday();
        $prizeDist = Participant::prizeDistribution();
        require __DIR__ . '/../Views/admin/reports.php';
    }

    // ── Staff Users ───────────────────────────────────────────────────────

    public function staffUsers(): void
    {
        Auth::adminCheck();
        $staff = User::allStaff();
        require __DIR__ . '/../Views/admin/staff_users.php';
    }

    public function staffCreate(): void
    {
        Auth::adminCheck();
        Csrf::check();

        $name = trim($_POST['name'] ?? '');
        $pin  = trim($_POST['pin'] ?? '');

        if (!$name) {
            $_SESSION['flash_error'] = 'İsim zorunlu.';
            Response::redirect('/admin/staff-users');
        }

        if (!preg_match('/^\d{6}$/', $pin)) {
            $_SESSION['flash_error'] = 'PIN tam 6 haneli rakam olmalı.';
            Response::redirect('/admin/staff-users');
        }

        if (User::pinCollides($pin)) {
            $_SESSION['flash_error'] = 'Bu PIN başka bir görevlide kullanılıyor.';
            Response::redirect('/admin/staff-users');
        }

        $pinHash = password_hash($pin, PASSWORD_BCRYPT);
        $id = User::createStaff($name, $pinHash, Auth::adminId());
        AuditLog::write(Auth::adminId(), 'staff.created', 'user', $id, ['name' => $name], $this->ip());

        $_SESSION['flash_pin'] = ['name' => $name, 'pin' => $pin];
        Response::redirect('/admin/staff-users');
    }

    public function staffGeneratePin(): void
    {
        Auth::adminCheck();
        try {
            $pin = User::generateUniquePin();
            Response::json(['ok' => true, 'pin' => $pin]);
        } catch (\RuntimeException $e) {
            Response::json(['ok' => false, 'error' => 'PIN üretilemedi.'], 500);
        }
    }

    public function staffResetPin(string $id): void
    {
        Auth::adminCheck();
        Csrf::check();

        $pin = trim($_POST['pin'] ?? '');
        if (!preg_match('/^\d{6}$/', $pin)) {
            $_SESSION['flash_error'] = 'PIN tam 6 haneli rakam olmalı.';
            Response::redirect('/admin/staff-users');
        }

        if (User::pinCollides($pin, (int)$id)) {
            $_SESSION['flash_error'] = 'Bu PIN başka bir görevlide kullanılıyor.';
            Response::redirect('/admin/staff-users');
        }

        User::updatePin((int)$id, password_hash($pin, PASSWORD_BCRYPT));
        AuditLog::write(Auth::adminId(), 'staff.pin_reset', 'user', (int)$id, [], $this->ip());

        $user = User::findById((int)$id);
        $_SESSION['flash_pin'] = ['name' => $user['name'] ?? '', 'pin' => $pin];
        Response::redirect('/admin/staff-users');
    }

    public function staffToggle(string $id): void
    {
        Auth::adminCheck();
        Csrf::check();
        User::toggle((int)$id);
        AuditLog::write(Auth::adminId(), 'staff.toggled', 'user', (int)$id, [], $this->ip());
        Response::redirect('/admin/staff-users');
    }

    public function staffDelete(string $id): void
    {
        Auth::adminCheck();
        Csrf::check();
        User::delete((int)$id);
        AuditLog::write(Auth::adminId(), 'staff.deleted', 'user', (int)$id, [], $this->ip());
        Response::redirect('/admin/staff-users');
    }

    // ── Admin Users ───────────────────────────────────────────────────────

    public function admins(): void
    {
        Auth::adminCheck();
        $admins = User::allAdmins();
        $currentId = Auth::adminId();
        require __DIR__ . '/../Views/admin/admins.php';
    }

    public function adminCreate(): void
    {
        Auth::adminCheck();
        Csrf::check();

        $name     = trim($_POST['name'] ?? '');
        $email    = trim(strtolower($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (!$name || !$email) {
            $_SESSION['flash_error'] = 'İsim ve e-posta zorunlu.';
            Response::redirect('/admin/admins');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) && !preg_match('/^[a-z0-9._-]+@[a-z]+$/', $email)) {
            $_SESSION['flash_error'] = 'Geçerli bir e-posta girin.';
            Response::redirect('/admin/admins');
        }
        if (strlen($password) < 8) {
            $_SESSION['flash_error'] = 'Parola en az 8 karakter olmalı.';
            Response::redirect('/admin/admins');
        }
        if (User::emailExists($email)) {
            $_SESSION['flash_error'] = 'Bu e-posta zaten kayıtlı.';
            Response::redirect('/admin/admins');
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $id = User::createAdmin($name, $email, $hash, Auth::adminId());
        AuditLog::write(Auth::adminId(), 'admin.created', 'user', $id, ['email' => $email], $this->ip());

        $_SESSION['flash_success'] = "Admin eklendi: {$email}";
        Response::redirect('/admin/admins');
    }

    public function adminUpdate(string $id): void
    {
        Auth::adminCheck();
        Csrf::check();

        $user = User::findById((int)$id);
        if (!$user || $user['role'] !== 'admin') Response::abort(404);

        $name  = trim($_POST['name'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));

        if (!$name || !$email) {
            $_SESSION['flash_error'] = 'İsim ve e-posta zorunlu.';
            Response::redirect('/admin/admins');
        }
        if (User::emailExists($email, (int)$id)) {
            $_SESSION['flash_error'] = 'Bu e-posta başka bir admin tarafından kullanılıyor.';
            Response::redirect('/admin/admins');
        }

        User::updateAdmin((int)$id, $name, $email);
        AuditLog::write(Auth::adminId(), 'admin.updated', 'user', (int)$id, ['email' => $email], $this->ip());

        $_SESSION['flash_success'] = 'Admin güncellendi.';
        Response::redirect('/admin/admins');
    }

    public function adminPasswordChange(string $id): void
    {
        Auth::adminCheck();
        Csrf::check();

        $user = User::findById((int)$id);
        if (!$user || $user['role'] !== 'admin') Response::abort(404);

        $isSelf  = ((int)$id === Auth::adminId());
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        // Kendi şifresini değiştiriyorsa mevcut şifre doğrulaması zorunlu
        if ($isSelf) {
            if (!password_verify($current, $user['password_hash'])) {
                $_SESSION['flash_error'] = 'Mevcut parolanız hatalı.';
                Response::redirect('/admin/admins');
            }
        }

        if (strlen($new) < 8) {
            $_SESSION['flash_error'] = 'Yeni parola en az 8 karakter olmalı.';
            Response::redirect('/admin/admins');
        }
        if ($new !== $confirm) {
            $_SESSION['flash_error'] = 'Parolalar eşleşmiyor.';
            Response::redirect('/admin/admins');
        }

        User::updatePassword((int)$id, password_hash($new, PASSWORD_BCRYPT));
        AuditLog::write(Auth::adminId(), 'admin.password_changed', 'user', (int)$id, [], $this->ip());

        $_SESSION['flash_success'] = $isSelf ? 'Parolanız güncellendi.' : 'Admin parolası güncellendi.';
        Response::redirect('/admin/admins');
    }

    public function adminToggle(string $id): void
    {
        Auth::adminCheck();
        Csrf::check();

        $targetId = (int)$id;

        if ($targetId === Auth::adminId()) {
            $_SESSION['flash_error'] = 'Kendinizi pasifleştiremezsiniz.';
            Response::redirect('/admin/admins');
        }

        $user = User::findById($targetId);
        if (!$user || $user['role'] !== 'admin') Response::abort(404);

        // Son aktif admin pasifleştirilemez
        if ($user['is_active'] && User::activeAdminCount() <= 1) {
            $_SESSION['flash_error'] = 'En az bir aktif admin kalmalı.';
            Response::redirect('/admin/admins');
        }

        User::toggleAdmin($targetId);
        AuditLog::write(Auth::adminId(), 'admin.toggled', 'user', $targetId, [], $this->ip());
        Response::redirect('/admin/admins');
    }

    public function adminDelete(string $id): void
    {
        Auth::adminCheck();
        Csrf::check();

        $targetId = (int)$id;

        if ($targetId === Auth::adminId()) {
            $_SESSION['flash_error'] = 'Kendinizi silemezsiniz.';
            Response::redirect('/admin/admins');
        }

        $user = User::findById($targetId);
        if (!$user || $user['role'] !== 'admin') Response::abort(404);

        if (User::activeAdminCount() <= 1 && $user['is_active']) {
            $_SESSION['flash_error'] = 'Son aktif admin silinemez.';
            Response::redirect('/admin/admins');
        }

        User::deleteAdmin($targetId);
        AuditLog::write(Auth::adminId(), 'admin.deleted', 'user', $targetId, ['email' => $user['email']], $this->ip());
        $_SESSION['flash_success'] = 'Admin silindi.';
        Response::redirect('/admin/admins');
    }

    // ── Logo Upload ───────────────────────────────────────────────────────

    private function handleLogoUpload(?string $existing = null): ?string
    {
        if (empty($_FILES['logo']['tmp_name'])) return $existing;

        $file     = $_FILES['logo'];
        $maxSize  = 2 * 1024 * 1024;
        $allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $extMap   = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp', 'image/svg+xml' => 'svg'];

        if ($file['size'] > $maxSize) {
            $_SESSION['flash_error'] = 'Logo 2MB\'den büyük olamaz.';
            Response::redirect('/admin/prizes');
        }

        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $allowed, true)) {
            $_SESSION['flash_error'] = 'Geçersiz logo formatı.';
            Response::redirect('/admin/prizes');
        }

        $ext      = $extMap[$mime];
        $filename = bin2hex(random_bytes(8)) . '.' . $ext;
        $dest     = __DIR__ . '/../../uploads/' . $filename;
        if (!is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0775, true);
        }
        move_uploaded_file($file['tmp_name'], $dest);
        return '/uploads/' . $filename;
    }
}
