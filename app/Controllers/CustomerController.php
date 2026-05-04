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
use App\Services\CodeService;
use App\Services\EligibilityChecker;
use App\Services\WheelEngine;

class CustomerController
{
    private function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    public function enterCode(): void
    {
        Auth::startSession();
        Auth::clearSpinCode();
        $error    = $_SESSION['spin_error'] ?? null;
        $settings = Settings::all();
        unset($_SESSION['spin_error']);
        require __DIR__ . '/../Views/customer/enter_code.php';
    }

    public function validateCode(): void
    {
        Auth::startSession();
        Csrf::check();
        RateLimit::check('spin_code:' . $this->ip(), 30, 300);

        $code = trim($_POST['code'] ?? '');
        $row  = (new CodeService())->validate($code);

        if (!$row) {
            $_SESSION['spin_error'] = 'Bu kod geçersiz veya süresi dolmuş.';
            Response::redirect('/');
        }

        Auth::setSpinCode($row);
        Response::redirect('/spin/register');
    }

    public function registerForm(): void
    {
        Auth::startSession();
        if (!Auth::getSpinCode()) Response::redirect('/');
        $error    = $_SESSION['spin_reg_error'] ?? null;
        $settings = Settings::all();
        unset($_SESSION['spin_reg_error']);
        require __DIR__ . '/../Views/customer/register.php';
    }

    public function registerPost(): void
    {
        Auth::startSession();
        Csrf::check();

        $code = Auth::getSpinCode();
        if (!$code) Response::redirect('/');

        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name']  ?? '');
        $phone     = preg_replace('/\D/', '', trim($_POST['phone'] ?? ''));
        $prefix    = trim($_POST['phone_prefix'] ?? '+90');
        $kvkk      = $_POST['kvkk'] ?? '';

        if (!$firstName || !$lastName || strlen($phone) < 7) {
            $_SESSION['spin_reg_error'] = 'Lütfen tüm alanları doldurun.';
            Response::redirect('/spin/register');
        }

        if (!$kvkk) {
            $_SESSION['spin_reg_error'] = 'KVKK onayı zorunludur.';
            Response::redirect('/spin/register');
        }

        $fullPhone = $prefix . $phone;
        $checker   = new EligibilityChecker();
        $check     = $checker->canParticipate($fullPhone);

        if (!$check['ok']) {
            $_SESSION['spin_reg_error'] = $check['message'];
            Response::redirect('/spin/register');
        }

        Auth::setRegistration([
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'phone'      => $fullPhone,
        ]);

        Response::redirect('/spin/wheel');
    }

    public function wheel(): void
    {
        Auth::startSession();
        if (!Auth::getSpinCode() || !Auth::getRegistration()) Response::redirect('/');
        $prizes   = Prize::allActive();
        $settings = Settings::all();
        require __DIR__ . '/../Views/customer/wheel.php';
    }

    public function win(string $participantId): void
    {
        Auth::startSession();
        $participant = Participant::find((int)$participantId);
        if (!$participant) Response::redirect('/');

        $prize    = Prize::find((int)$participant['prize_id']);
        $settings = Settings::all();
        Auth::clearSpinCode();
        require __DIR__ . '/../Views/customer/win.php';
    }

    public function reset(): void
    {
        Auth::startSession();
        $code = Auth::getSpinCode();
        if ($code) {
            (new CodeService())->cancelBySession((int)$code['id']);
        }
        Auth::clearCustomer();
        Response::json(['ok' => true]);
    }
}
