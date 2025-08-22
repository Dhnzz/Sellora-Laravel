<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\MonthlyBookClosing;
use App\Services\ML\LstmPredictor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\MonthlyRevenuePrediction;

class MonthlyBookClosingController
{
    public function index()
    {
        $data = [
            'title' => 'Tutup Buku Bulanan',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'closing_create',
            'breadcrumbs' => [
                [
                    'name' => 'Tutup Buku Bulanan',
                    'link' => '#',
                ],
                [
                    'name' => 'Data Tutup Buku',
                    'link' => route('owner.closing.index'),
                ],
            ],
            'monthly_closings' => MonthlyBookClosing::all(),
        ];

        return view('owner.closing.index', compact('data'));
    }

    public function create()
    {
        // 1) Ambil tutup buku terakhir
        $last = MonthlyBookClosing::orderByDesc('year')->orderByDesc('month')->first();

        // 2) Tentukan periode target
        if ($last) {
            [$year, $month] = $this->nextPeriod((int) $last->year, (int) $last->month);
        } else {
            $now = now(); // kalau belum ada, pakai bulan & tahun saat ini
            $year = (int) $now->year;
            $month = (int) $now->month;
        }

        // 3) Hitung total pendapatan bersih dari seluruh transaksi di periode target
        $suggestedProfit = $this->computeNetProfit($year, $month);

        $data = [
            'title' => 'Tutup Buku Bulanan',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'closing_create',
            'breadcrumbs' => [
                [
                    'name' => 'Tutup Buku Bulanan',
                    'link' => '#',
                ],
                [
                    'name' => 'Data Tutup Buku',
                    'link' => route('owner.closing.index'),
                ],
                [
                    'name' => 'Tambah Tutup Buku',
                    'link' => route('owner.closing.create'),
                ],
            ],
        ];
        // form sederhana tutup buku
        return view('owner.closing.create', compact('data', 'year', 'month', 'suggestedProfit'));
    }

    public function store(Request $req, LstmPredictor $predictor)
    {
        $req->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'total_profit' => 'nullable|numeric',
            'threshold_profit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $year = (int) $req->year;
        $month = (int) $req->month;

        // Jika total_profit kosong/0, auto-akumulasi dari transaksi periode tsb
        $totalProfit = $req->filled('total_profit') && (float) $req->total_profit > 0 ? (float) $req->total_profit : $this->computeNetProfit($year, $month);

        // Simpan / update tutup buku
        $closing = MonthlyBookClosing::updateOrCreate(
            ['year' => $year, 'month' => $month],
            [
                'total_profit' => $totalProfit,
                'closed_at' => now(),
                'notes' => $req->notes,
            ],
        );

        // === PREDIKSI LSTM ===
        // Ambil historis semua tutup buku (minimal 12 bulan)
        $rows = DB::table('monthly_book_closings')->selectRaw("CONCAT(year,'-',LPAD(month,2,'0'),'-01') as date, total_profit as value")->orderBy('year')->orderBy('month')->get()->map(fn($r) => ['date' => $r->date, 'value' => (float) $r->value])->toArray();

        if (count($rows) < 12) {
            return redirect()->route('owner.predictions.index')->with('warning', 'Tutup buku tersimpan. Historis < 12 bulan, prediksi LSTM dilewati dulu.');
        }

        $cfg = config('services.recsys');
        $lookBack = (int) ($cfg['lstm_look_back'] ?? 6);
        $out = $predictor->predictNext($rows, $lookBack);

        // Tentukan bulan depan dari periode yang barusan ditutup
        $lastDate = Carbon::create($year, $month, 1)->endOfMonth();
        $nextDate = $lastDate->copy()->addMonthNoOverflow()->startOfMonth();
        $nextYear = (int) ($out['next_year'] ?? $nextDate->year);
        $nextMonth = (int) ($out['next_month'] ?? $nextDate->month);

        $predicted = (float) $out['predicted_profit'];
        $lastActual = (float) ($out['meta']['last_actual'] ?? end($rows)['value']);
        $pctChange = $lastActual > 0 ? (($predicted - $lastActual) / $lastActual) * 100 : null;

        // Tentukan threshold: pakai input user > ENV > fallback lastActual
        $threshold = $req->filled('threshold_profit') ? (float) $req->threshold_profit : (is_null($cfg['profit_threshold_absolute']) ? $lastActual : (float) $cfg['profit_threshold_absolute']);

        $gap = $threshold - $predicted;
        $cashPlan = $this->buildCashflowPlan($gap, $predicted, $lastActual);

        MonthlyRevenuePrediction::updateOrCreate(
            ['year' => $nextYear, 'month' => $nextMonth],
            [
                'predicted_profit' => $predicted,
                'threshold_profit' => $threshold,
                'is_profitable' => $predicted >= $threshold,
                'pct_change_vs_last' => $pctChange,
                'model_version' => $cfg['lstm_model_version'] ?? 'v1.0',
                'meta' => [
                    'look_back' => $lookBack,
                    'last_actual' => $lastActual,
                    'gap_to_target' => $gap,
                    'python_meta' => $out['meta'] ?? [],
                    'cashflow_plan' => $cashPlan,
                ],
            ],
        );

        return redirect()->route('owner.predictions.index')->with('success', 'Tutup buku berhasil. Prediksi bulan depan sudah dibuat otomatis.');
    }

    public function edit(MonthlyBookClosing $closing)
    {
        $data = [
            'title' => 'Tutup Buku Bulanan',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'closing_create',
            'breadcrumbs' => [
                [
                    'name' => 'Tutup Buku Bulanan',
                    'link' => '#',
                ],
                [
                    'name' => 'Edit Tutup Buku',
                    'link' => route('owner.closing.edit', $closing->id),
                ],
            ],
            'monthly_closing' => $closing,
        ];

        return view('owner.closing.edit', compact('data'));
    }

    private function buildCashflowPlan(float $gap, float $predicted, float $lastActual): array
    {
        $plan = [
            'status' => $gap > 0 ? 'DEFISIT' : 'SURPLUS',
            'purchase_budget_next_month' => $gap > 0 ? round($gap, 2) : 0.0,
            'suggestions' => [],
        ];

        if ($gap > 0) {
            $plan['suggestions'][] = 'Fokus stok pada bundle/top-SKU (asosiasi FP-Growth tertinggi).';
            $plan['suggestions'][] = 'Kurangi pembelian SKU slow-moving 20–30%.';
            $plan['suggestions'][] = 'Negosiasi tempo pembayaran supplier.';
            $plan['suggestions'][] = 'Dorong SKU high-margin untuk nutup gap.';
            $abs = abs($gap);
            $plan['allocation'] = [['bucket' => 'Top Bundles/Associations', 'percent' => 50, 'nominal' => round($abs * 0.5, 2)], ['bucket' => 'High-Margin SKUs', 'percent' => 30, 'nominal' => round($abs * 0.3, 2)], ['bucket' => 'Promo/Marketing', 'percent' => 20, 'nominal' => round($abs * 0.2, 2)]];
        } else {
            $plan['suggestions'][] = 'Tambah safety stock 10–15% untuk fast-moving.';
            $plan['suggestions'][] = 'Eksperimen bundling & upsell cross-sell.';
            $plan['suggestions'][] = 'Sisihkan sebagian untuk cadangan kas.';
            $plan['allocation'] = [['bucket' => 'Safety Stock Fast-Moving', 'percent' => 60, 'note' => '+10–15%'], ['bucket' => 'Eksperimen Bundle Promo', 'percent' => 25], ['bucket' => 'Cadangan Kas', 'percent' => 15]];
        }
        return $plan;
    }

    /** Dapatkan bulan berikutnya (Des → Jan + tahun++) */
    private function nextPeriod(int $year, int $month): array
    {
        if ($month >= 12) {
            return [$year + 1, 1];
        }
        return [$year, $month + 1];
    }

    /**
     * Akumulasi pendapatan bersih dari seluruh transaksi pada (year, month).
     * Sesuaikan dengan skema tabel kamu:
     * - Kalau ada kolom 'net_profit' di sales_transactions → langsung sum.
     * - Kalau nggak ada, hitung dari items: qty * (unit_price - unit_cost) - diskon_item - porsi diskon_invoice.
     */
    private function computeNetProfit(int $year, int $month): float
    {
        $rows = DB::table('sales_transactions as st')
            ->leftJoin('sales_transaction_items as sti', 'st.id', '=', 'sti.sales_transaction_id')
            ->whereYear('st.invoice_date', $year)
            ->whereMonth('st.invoice_date', $month)
            ->selectRaw(
                "
            st.id,
            COALESCE(SUM(COALESCE(sti.quantity_sold,0) * COALESCE(sti.msu_price,0)), 0) AS gross_items,
            MAX(COALESCE(st.discount_percent, 0)) AS discount_percent,
            MAX(COALESCE(st.initial_total_amount, 0)) AS initial_total_amount,
            MAX(COALESCE(st.final_total_amount, 0)) AS final_total_amount
        ",
            )
            ->groupBy('st.id')
            ->get();

        $total = 0.0;
        foreach ($rows as $r) {
            $gross = (float) $r->gross_items;
            $final = (float) $r->final_total_amount;
            $discPc = (float) $r->discount_percent;

            if ($final > 0) {
                $net = $final;
            } elseif ($discPc > 0) {
                $net = $gross * (1 - $discPc / 100);
            } else {
                $net = $gross;
            }

            // (Opsional) kalau kamu yakin initial_total_amount lebih akurat utk kasus tertentu:
            // $initial = (float) $r->initial_total_amount;
            // if ($initial > 0 && $final == 0 && $discPc == 0) {
            //     $net = $initial;
            // }

            $total += $net;
        }

        return (float) round($total, 2);
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
