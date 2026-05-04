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
        $file    = $_FILES[$field];
        $maxSize = $maxMb * 1024 * 1024;
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $extMap  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif',
                    'image/webp' => 'webp', 'image/svg+xml' => 'svg'];

        if ($file['size'] > $maxSize) {
            $_SESSION['flash_error'] = "Dosya {$maxMb}MB'den büyük olamaz.";
            return null;
        }
        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $allowed, true)) {
            $_SESSION['flash_error'] = 'Geçersiz dosya formatı.';
            return null;
        }
        $ext      = $extMap[$mime];
        $filename = $field . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $destDir  = __DIR__ . '/../../public/uploads';
        if (!is_dir($destDir)) mkdir($destDir, 0775, true);
        move_uploaded_file($file['tmp_name'], $destDir . '/' . $filename);
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
        $dest     = __DIR__ . '/../../public/uploads/' . $filename;
        if (!is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0775, true);
        }
        move_uploaded_file($file['tmp_name'], $dest);
        return '/uploads/' . $filename;
    }
}
