<?php

namespace App\Services;

use App\Models\Participant;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportService
{
    public function exportParticipants(array $filters): string
    {
        $rows = Participant::filterAll($filters);

        $ss    = new Spreadsheet();
        $sheet = $ss->getActiveSheet();

        $sheet->fromArray(
            [['ID', 'Ad', 'Soyad', 'Telefon', 'Hediye', 'Marka', 'Fiş No', 'Tutar (TL)', 'Görevli', 'Tarih']],
            null, 'A1'
        );

        $row = 2;
        foreach ($rows as $r) {
            $sheet->fromArray([[
                $r['id'],
                $r['first_name'],
                $r['last_name'],
                $r['phone'],
                $r['prize_name_snapshot'],
                $r['brand_snapshot'] ?? '',
                $r['receipt_no'] ?? '',
                $r['receipt_amount'] ?? '',
                $r['staff_name'] ?? '',
                $r['created_at'],
            ]], null, "A{$row}");
            $row++;
        }

        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $path = __DIR__ . '/../../storage/exports/katilimcilar_' . date('Ymd_His') . '.xlsx';
        (new Xlsx($ss))->save($path);
        return $path;
    }
}
