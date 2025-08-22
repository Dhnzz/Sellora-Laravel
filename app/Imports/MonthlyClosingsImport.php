<?php

namespace App\Imports;

use App\Models\MonthlyBookClosing;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

class MonthlyClosingsImport implements ToCollection, WithHeadingRow, SkipsOnFailure, SkipsOnError
{
    public array $summary = [
        'inserted' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => [],
    ];

    // HeadingRow akan otomatis bikin "Tahun" -> "tahun", "Total Profit" -> "total_profit"
    public function collection(Collection $rows)
    {
        foreach ($rows as $i => $row) {
            // Normalisasi header
            $year = (int) ($row['tahun'] ?? ($row['year'] ?? null));
            $month = (int) ($row['bulan'] ?? ($row['month'] ?? null));
            $profitRaw = $row['total_profit'] ?? ($row['profit'] ?? null);

            // Bersihin angka (handle "Rp 1.234.567,89" atau "1,234,567.89")
            $profit = $this->parseNumber($profitRaw);

            // Validasi baris
            if (!$this->validRow($year, $month, $profit)) {
                $this->summary['skipped']++;
                $this->summary['errors'][] = 'Baris #' . ($i + 2) . ' tidak valid (tahun/bulan/profit).';
                continue;
            }

            // Upsert by (year, month)
            $exists = MonthlyBookClosing::query()->where('year', $year)->where('month', $month)->first();

            $payload = [
                'total_profit' => $profit,
                // jangan set closed_at di import massal, biar historis aja
            ];

            if ($exists) {
                $exists->update($payload);
                $this->summary['updated']++;
            } else {
                MonthlyBookClosing::create(
                    array_merge(
                        [
                            'year' => $year,
                            'month' => $month,
                        ],
                        $payload,
                    ),
                );
                $this->summary['inserted']++;
            }
        }
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $f) {
            $this->summary['errors'][] = "Row {$f->row()}: " . implode(', ', $f->errors());
            $this->summary['skipped']++;
        }
    }

    public function onError(Throwable $e)
    {
        $this->summary['errors'][] = $e->getMessage();
    }

    private function validRow(?int $year, ?int $month, $profit): bool
    {
        if (!$year || $year < 2000 || $year > 2100) {
            return false;
        }
        if (!$month || $month < 1 || $month > 12) {
            return false;
        }
        if (!is_numeric($profit)) {
            return false;
        }
        return true;
    }

    private function parseNumber($val): float
    {
        if (is_null($val) || $val === '') {
            return 0.0;
        }
        if (is_numeric($val)) {
            return (float) $val;
        }

        // Hilangkan "Rp", spasi
        $s = trim(str_ireplace(['rp', ' '], '', (string) $val));
        // Deteksi format (Indonesia vs US)
        // Kasus "1.234.567,89"
        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $s)) {
            $s = str_replace('.', '', $s); // remove thousand sep
            $s = str_replace(',', '.', $s); // comma -> dot
        } else {
            // Kasus "1,234,567.89"
            $parts = substr_count($s, ',');
            $dots = substr_count($s, '.');
            if ($parts > 0 && $dots > 0 && strrpos($s, '.') > strrpos($s, ',')) {
                $s = str_replace(',', '', $s); // remove thousand sep
            }
        }
        return (float) ($s === '' ? 0 : $s);
    }
}
