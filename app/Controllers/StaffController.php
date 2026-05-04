<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\RateLimit;
use App\Core\Response;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\CodeService;
use App\Services\EligibilityChecker;

class StaffController
{
    private function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    public function loginForm(): void
    {
        if (Auth::staffId()) Response::redirect('/staff');
        require __DIR__ . '/../Views/staff/login.php';
    }

    public function loginPost(): void
    {
        Csrf::check();
        $ip = $this->ip();
        RateLimit::check('staff_login:' . $ip, 10, 900);

        $pin = trim($_POST['pin'] ?? '');

        if (!preg_match('/^\d{6}$/', $pin)) {
            $_SESSION['staff_error'] = 'Geçersiz PIN formatı.';
            Response::redirect('/staff/login');
        }

        $rows = User::activeStaffPinHashes();
        foreach ($rows as $row) {
            if (password_verify($pin, $row['pin_hash'])) {
                RateLimit::reset('staff_login:' . $ip);
                $user = User::findById($row['id']);
                Auth::staffLogin($user['id'], $user['name']);
                User::touchLastLogin($user['id']);
                AuditLog::write($user['id'], 'staff.login', null, null, [], $ip);
                Response::redirect('/staff');
            }
        }

        AuditLog::write(null, 'staff.login_failed', null, null, [], $ip);
        $_SESSION['staff_error'] = 'PIN hatalı.';
        Response::redirect('/staff/login');
    }

    public function logout(): void
    {
        $id = Auth::staffId();
        Auth::staffLogout();
        AuditLog::write($id, 'staff.logout', null, null, [], $this->ip());
        Response::redirect('/staff/login');
    }

    public function approveForm(): void
    {
        Auth::staffCheck();
        require __DIR__ . '/../Views/staff/approve.php';
    }

    public function approvePost(): void
    {
        Auth::staffCheck();
        Csrf::check();

        $checker = new EligibilityChecker();
        $check   = $checker->eventIsOpen();

        if (!$check['ok']) {
            $_SESSION['staff_error'] = match($check['reason']) {
                'EVENT_INACTIVE' => 'Etkinlik şu anda aktif değil.',
                'OUT_OF_HOURS'   => 'Etkinlik saatleri dışındasınız.',
                default          => 'Onay verilemiyor.',
            };
            Response::redirect('/staff');
        }

        $receiptNo = trim($_POST['receipt_no'] ?? '') ?: null;
        $amount    = isset($_POST['receipt_amount']) && $_POST['receipt_amount'] !== '' ? (float)$_POST['receipt_amount'] : null;

        try {
            $code = (new CodeService())->issue(Auth::staffId(), $receiptNo, $amount);
            AuditLog::write(Auth::staffId(), 'code.issued', 'spin_codes', null, [], $this->ip());
            $_SESSION['issued_code'] = $code;
            Response::redirect('/staff');
        } catch (\RuntimeException $e) {
            $_SESSION['staff_error'] = 'Kod üretilemedi. Stok veya sistem hatası.';
            Response::redirect('/staff');
        }
    }

    public function cancelCode(string $codeId): void
    {
        Auth::staffCheck();
        Csrf::check();

        $ok = (new CodeService())->cancel((int)$codeId, Auth::staffId());
        AuditLog::write(Auth::staffId(), 'code.cancelled', 'spin_codes', (int)$codeId, [], $this->ip());

        unset($_SESSION['issued_code']);
        Response::redirect('/staff');
    }

    public function reject(): void
    {
        Auth::staffCheck();
        Csrf::check();
        AuditLog::write(Auth::staffId(), 'approval.rejected', null, null, [], $this->ip());
        $_SESSION['staff_message'] = 'Müşteri reddedildi.';
        Response::redirect('/staff');
    }
}
