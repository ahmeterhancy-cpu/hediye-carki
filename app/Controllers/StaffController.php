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
use App\Models\User;
use App\Services\EligibilityChecker;
use App\Services\WheelEngine;

class StaffController
{
    private function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    // ── Login / Logout ────────────────────────────────────────────────

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

    // ── Müşteri kayıt formu ──────────────────────────────────────────

    public function customerForm(): void
    {
        Auth::staffCheck();
        $error    = $_SESSION['staff_error'] ?? null;
        $message  = $_SESSION['staff_message'] ?? null;
        $settings = Settings::all();
        unset($_SESSION['staff_error'], $_SESSION['staff_message']);

        $checker = new EligibilityChecker();
        $eventCheck = $checker->eventIsOpen();

        require __DIR__ . '/../Views/staff/customer_form.php';
    }

    public function customerSubmit(): void
    {
        Auth::staffCheck();
        Csrf::check();

        $checker = new EligibilityChecker();
        $eventCheck = $checker->eventIsOpen();

        if (!$eventCheck['ok']) {
            $_SESSION['staff_error'] = match($eventCheck['reason']) {
                'EVENT_INACTIVE' => 'Etkinlik şu anda aktif değil.',
                'OUT_OF_HOURS'   => 'Etkinlik saatleri dışındasınız.',
                default          => 'Çekiliş yapılamıyor.',
            };
            Response::redirect('/staff');
        }

        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name']  ?? '');
        $phone     = preg_replace('/\D/', '', trim($_POST['phone'] ?? ''));
        $prefix    = trim($_POST['phone_prefix'] ?? '+90');
        $kvkk      = $_POST['kvkk'] ?? '';
        $receiptNo = trim($_POST['receipt_no'] ?? '') ?: null;
        $receiptAmt = isset($_POST['receipt_amount']) && $_POST['receipt_amount'] !== ''
            ? (float)$_POST['receipt_amount'] : null;

        if (!$firstName || !$lastName || strlen($phone) < 7) {
            $_SESSION['staff_error'] = 'Lütfen ad, soyad ve telefonu eksiksiz doldurun.';
            Response::redirect('/staff');
        }

        if (!$kvkk) {
            $_SESSION['staff_error'] = 'KVKK onayı zorunludur.';
            Response::redirect('/staff');
        }

        $fullPhone = $prefix . $phone;
        $check     = $checker->canParticipate($fullPhone);

        if (!$check['ok']) {
            $_SESSION['staff_error'] = $check['message'];
            Response::redirect('/staff');
        }

        // Müşteri verisini session'a yaz, çark sayfasına geç
        $_SESSION['pending_customer'] = [
            'first_name'     => $firstName,
            'last_name'      => $lastName,
            'phone'          => $fullPhone,
            'receipt_no'     => $receiptNo,
            'receipt_amount' => $receiptAmt,
        ];

        Response::redirect('/staff/spin');
    }

    // ── Çark ekranı ───────────────────────────────────────────────────

    public function spin(): void
    {
        Auth::staffCheck();
        if (empty($_SESSION['pending_customer'])) Response::redirect('/staff');

        $prizes   = Prize::allActive();
        $settings = Settings::all();
        $customer = $_SESSION['pending_customer'];

        require __DIR__ . '/../Views/staff/spin.php';
    }

    public function spinExecute(): void
    {
        Auth::staffCheck();

        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Csrf::verify($token)) {
            Response::json(['ok' => false, 'error' => 'invalid_csrf'], 403);
        }

        $customer = $_SESSION['pending_customer'] ?? null;
        if (!$customer) {
            Response::json(['ok' => false, 'error' => 'invalid_session'], 400);
        }

        // Aynı müşteri için tekrar tıklanmasın
        if (!empty($_SESSION['pending_customer']['_locked'])) {
            Response::json(['ok' => false, 'error' => 'already_spun'], 409);
        }
        $_SESSION['pending_customer']['_locked'] = true;

        try {
            $engine    = new WheelEngine();
            $winner    = $engine->pickWinner();
            $allPrizes = Prize::allActive();

            // pickup_location dilim ayarlarından, yoksa "{Marka} standı" fallback
            $pickup = !empty($winner['pickup_location'])
                ? $winner['pickup_location']
                : (!empty($winner['brand_name']) ? $winner['brand_name'] . ' standı' : 'Etkinlik standı');

            $participantId = Participant::create([
                'first_name'          => $customer['first_name'],
                'last_name'           => $customer['last_name'],
                'phone'               => $customer['phone'],
                'receipt_no'          => $customer['receipt_no'],
                'receipt_amount'      => $customer['receipt_amount'],
                'prize_id'            => $winner['id'],
                'prize_name_snapshot' => $winner['name'],
                'brand_snapshot'      => $winner['brand_name'] ?? null,
                'pickup_snapshot'     => $pickup,
                'staff_id'            => Auth::staffId(),
                'ip_address'          => $this->ip(),
                'user_agent'          => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ]);

            AuditLog::write(Auth::staffId(), 'spin.executed', 'participants', $participantId,
                ['prize_id' => $winner['id']], $this->ip());

            $targetAngle = $engine->calculateTargetAngle($winner['id'], $allPrizes);

            unset($_SESSION['pending_customer']);

            Response::json([
                'ok'             => true,
                'winner'         => [
                    'id'         => $winner['id'],
                    'name'       => $winner['name'],
                    'brand_name' => $winner['brand_name'],
                    'logo_path'  => $winner['logo_path'],
                    'color_hex'  => $winner['color_hex'],
                ],
                'target_angle'   => $targetAngle,
                'participant_id' => $participantId,
            ]);

        } catch (\Throwable $e) {
            // Hata durumunda lock'u serbest bırak
            unset($_SESSION['pending_customer']['_locked']);

            \App\Core\Logger::error('staff_spin_failed', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile() . ':' . $e->getLine(),
            ]);

            $code = match($e->getMessage()) {
                'NO_STOCK_AVAILABLE'   => 'no_stock',
                'STOCK_RACE_CONDITION' => 'no_stock',
                default                => 'server_error',
            };
            $status = $code === 'no_stock' ? 410 : 500;
            Response::json(['ok' => false, 'error' => $code], $status);
        }
    }

    // ── Kazanma ekranı ────────────────────────────────────────────────

    public function win(string $participantId): void
    {
        Auth::staffCheck();
        $participant = Participant::find((int)$participantId);
        if (!$participant) Response::redirect('/staff');

        $prize    = Prize::find((int)$participant['prize_id']);
        $settings = Settings::all();
        require __DIR__ . '/../Views/staff/win.php';
    }

    // ── Yeni müşteri (form'a dön) ─────────────────────────────────────

    public function newCustomer(): void
    {
        Auth::staffCheck();
        Csrf::check();
        unset($_SESSION['pending_customer']);
        Response::redirect('/staff');
    }
}
