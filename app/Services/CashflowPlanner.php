<?php
namespace App\Services;

class CashflowPlanner
{
    public function buildPlan(float $predicted, float $threshold, ?float $lastActual): array
    {
        $procRatio = (float) config('prediction.procurement_ratio', 0.55);
        $adjCap = (float) config('prediction.cashflow_adj_cap', 0.2);
        $abcBase = config('prediction.abc_base', [60, 30, 10]);

        $baseBudget = $predicted * $procRatio;

        $gapValue = $threshold - $predicted; // positif = perlu hemat
        $denom = $lastActual ?? $predicted;
        $gapPct = $denom > 0 ? $gapValue / $denom : 0.0;

        // clamp penyesuaian
        $adj = $gapPct;
        if ($adj > $adjCap) {
            $adj = $adjCap;
        }
        if ($adj < -$adjCap) {
            $adj = -$adjCap;
        }

        // kalau gap > 0 (hemat), turunkan budget; gap < 0 (agresif), naikkan budget
        $suggestedBudget = round($baseBudget * (1 - $adj), 2);

        // alokasi ABC: fokus dinaikkan ke kelas A saat prediksi di atas target (gap < 0)
        $a = $abcBase[0];
        $b = $abcBase[1];
        $c = $abcBase[2];
        $shift = (int) round(abs($adj) * 10); // contoh: adj 0.2 -> shift 2%
        if ($gapValue <= 0) {
            // agresif → tambah porsi A
            $a += $shift;
            $b -= (int) ceil($shift / 2);
            $c -= (int) floor($shift / 2);
        } else {
            // hemat → kurangi porsi A sedikit, geser ke B
            $a -= $shift;
            $b += $shift; // C tetap
        }
        // normalisasi supaya total 100
        $sum = max($a + $b + $c, 1);
        $alloc = [
            'A' => max((int) round(($a / $sum) * 100), 0),
            'B' => max((int) round(($b / $sum) * 100), 0),
            'C' => max(100 - (int) round(($a / $sum) * 100) - (int) round(($b / $sum) * 100), 0),
        ];

        $policy = $gapValue > 0 ? 'Tighten' : ($gapValue < 0 ? 'Expand' : 'Neutral');
        $notes = $gapValue > 0 ? 'Prediksi di bawah target: kurangi pembelian perlahan, fokus fast-mover & hindari dead stock.' : ($gapValue < 0 ? 'Prediksi di atas target: amankan stok fast-mover & promosi bundling.' : 'Prediksi setara target: pertahankan pola pembelian bulan lalu.');

        return [
            'policy' => $policy,
            'budget_base' => round($baseBudget, 2),
            'budget_suggested' => $suggestedBudget,
            'gap_value' => round($gapValue, 2),
            'gap_pct_vs_ref' => round($gapPct * 100, 2),
            'allocation_pct' => $alloc, // {A,B,C} total 100
            'notes' => $notes,
        ];
    }
}
