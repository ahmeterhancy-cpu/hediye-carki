<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\RateLimit;
use App\Core\Response;
use App\Models\AuditLog;
use App\Models\Participant;
use App\Models\Prize;
use App\Models\Stock;
use App\Services\CodeService;
use App\Services\WheelEngine;

class ApiController
{
    private function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    public function spinExecute(): void
    {
        Auth::startSession();

        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!\App\Core\Csrf::verify($token)) {
            Response::json(['ok' => false, 'error' => 'invalid_csrf'], 403);
        }

        RateLimit::check('spin_execute:' . $this->ip(), 1, 10);

        $codeRow = Auth::getSpinCode();
        $regData = Auth::getRegistration();

        if (!$codeRow || !$regData) {
            Response::json(['ok' => false, 'error' => 'invalid_session'], 400);
        }

        $codeService = new CodeService();
        $fresh = $codeService->validate($codeRow['code']);
        if (!$fresh || (int)$fresh['id'] !== (int)$codeRow['id']) {
            Response::json(['ok' => false, 'error' => 'code_consumed'], 409);
        }

        try {
            $engine  = new WheelEngine();
            $winner  = $engine->pickWinner();
            $allPrizes = Prize::allActive();

            $participantId = Participant::create([
                'first_name'          => $regData['first_name'],
                'last_name'           => $regData['last_name'],
                'phone'               => $regData['phone'],
                'receipt_no'          => $codeRow['receipt_no'] ?? null,
                'receipt_amount'      => $codeRow['receipt_amount'] ?? null,
                'prize_id'            => $winner['id'],
                'prize_name_snapshot' => $winner['name'],
                'brand_snapshot'      => $winner['brand_name'] ?? null,
                'staff_id'            => $codeRow['staff_id'] ?? null,
                'ip_address'          => $this->ip(),
                'user_agent'          => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ]);

            $codeService->consume((int)$codeRow['id'], $participantId);
            AuditLog::write(null, 'spin.executed', 'participants', $participantId, ['prize_id' => $winner['id']], $this->ip());

            $targetAngle = $engine->calculateTargetAngle($winner['id'], $allPrizes);

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
                'all_prizes'     => $allPrizes,
            ]);

        } catch (\RuntimeException $e) {
            $code = match($e->getMessage()) {
                'NO_STOCK_AVAILABLE'   => 'no_stock',
                'STOCK_RACE_CONDITION' => 'no_stock',
                default                => 'server_error',
            };
            Response::json(['ok' => false, 'error' => $code], 410);
        }
    }

    public function stockStatus(): void
    {
        Auth::adminCheck();
        Response::json(Stock::status());
    }
}
