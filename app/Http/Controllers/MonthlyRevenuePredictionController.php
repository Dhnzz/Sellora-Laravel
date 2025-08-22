<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\ML\LstmPredictor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\MonthlyRevenuePrediction;

class MonthlyRevenuePredictionController
{
    public function index()
    {
        // ambil histori & prediksi untuk grafik
        $historical = DB::table('monthly_book_closings')->selectRaw("CONCAT(year, '-', LPAD(month,2,'0'), '-01') as ym, total_profit")->orderBy('year')->orderBy('month')->get();

        $preds = MonthlyRevenuePrediction::orderBy('year')->orderBy('month')->get();

        $data = [
            'title' => 'Prediksi',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'closing_create',
            'breadcrumbs' => [
                [
                    'name' => 'Prediksi Bulanan',
                    'link' => '#',
                ],
            ],
        ];

        return view('owner.predictions.index', compact('historical', 'preds', 'data'));
    }

    /**
     * Dipanggil saat admin/owner melakukan TUTUP BUKU bulan ini.
     * Request menyertakan threshold_profit (opsional disimpan per user/owner).
     */
    public function predictNext(Request $request, LstmPredictor $predictor)
    {
        $request->validate([
            'threshold_profit' => 'required|numeric|min:0',
        ]);

        // Ambil data historis dari DB (minimal look_back + cadangan)
        // Gunakan konfigurasi yang benar dengan fallback aman
        $cfg = config('services.recsys', []);
        $lookBack = (int) ($cfg['lstm_look_back'] ?? config('prediction.look_back', 6));

        $rows = DB::table('monthly_book_closings')->selectRaw("CONCAT(year, '-', LPAD(month,2,'0'), '-01') as date, total_profit as value")->orderBy('year')->orderBy('month')->get()->map(fn($r) => ['date' => $r->date, 'value' => (float) $r->value])->toArray();

        if (count($rows) < max(12, $lookBack + 6)) {
            return back()->with('error', 'Data historis terlalu sedikit untuk prediksi yang stabil.');
        }

        // Call Python
        $out = $predictor->predictNext($rows, $lookBack);

        // Guard: jika respons tidak valid atau gagal, hentikan dengan pesan yang jelas
        if (!is_array($out) || (isset($out['ok']) && $out['ok'] === false) || !array_key_exists('predicted_profit', $out)) {
            $message = is_array($out) ? $out['message'] ?? 'Prediksi gagal dari layanan ML.' : 'Prediksi gagal dari layanan ML.';
            return back()->with('error', $message);
        }

        // Tentukan next month (dari hasil Python atau hitung manual)
        // Python sudah kirim next_year & next_month. Kalau mau pakai manual:
        $last = end($rows);
        $lastDate = Carbon::parse($last['date'])->endOfMonth();
        $nextDate = $lastDate->copy()->addMonthNoOverflow()->startOfMonth();
        $nextYear = (int) $nextDate->format('Y');
        $nextMonth = (int) $nextDate->format('n');

        $predicted = (float) $out['predicted_profit'];
        $lastActual = (float) $out['meta']['last_actual'] ?? (float) $last['value'];
        $pctChange = $lastActual > 0 ? (($predicted - $lastActual) / $lastActual) * 100 : null;

        $threshold = (float) $request->threshold_profit;
        $gap = $threshold - $predicted; // >0 berarti defisit terhadap target

        $cashflowPlan = $this->buildCashflowRecommendation($gap, $predicted, $lastActual);

        $row = MonthlyRevenuePrediction::updateOrCreate(
            ['year' => $nextYear, 'month' => $nextMonth],
            [
                'predicted_profit' => $predicted,
                'threshold_profit' => $threshold,
                'is_profitable' => $predicted >= $threshold,
                'pct_change_vs_last' => $pctChange,
                'model_version' => (string) ($out['meta']['model_version'] ?? ($cfg['lstm_model_version'] ?? config('prediction.model_version', 'lstm_v1'))),
                'meta' => [
                    'look_back' => $lookBack,
                    'last_actual' => $lastActual,
                    'gap_to_target' => $gap,
                    'python_meta' => $out['meta'] ?? [],
                    'cashflow_plan' => $cashflowPlan,
                ],
            ],
        );

        return redirect()->route('owner.predictions.index')->with('success', 'Prediksi bulan depan berhasil dibuat.');
    }

    private function buildCashflowRecommendation(float $gap, float $predicted, float $lastActual): array
    {
        // Rule-of-thumb (bisa di-improve nanti pake FP-Growth & margin)
        // Semua angka % saran ini fleksibel—tujuannya jadi baseline.
        $plan = [
            'status' => $gap > 0 ? 'DEFISIT' : 'SURPLUS',
            'purchase_budget_next_month' => $gap > 0 ? $gap : 0.0, // nominal yang perlu ditutup
            'suggestions' => [],
        ];

        if ($gap > 0) {
            // target lebih tinggi dari prediksi → tutup gap
            $plan['suggestions'][] = 'Prioritaskan stok untuk bundle/top SKU hasil FP-Growth (kontribusi >60%).';
            $plan['suggestions'][] = 'Kurangi pembelian SKU slow-moving 20–30% dan optimalkan promo bundling.';
            $plan['suggestions'][] = 'Negosiasi tempo pembayaran supplier untuk menjaga arus kas.';
            $plan['suggestions'][] = 'Fokus PO pada SKU dengan margin tinggi untuk menutup gap lebih cepat.';
        } else {
            // aman / surplus
            $plan['suggestions'][] = 'Tambah safety stock 10–15% untuk SKU fast-moving/bundle unggulan.';
            $plan['suggestions'][] = 'Siapkan campaign upsell/cross-sell berbasis aturan FP-Growth.';
            $plan['suggestions'][] = 'Evaluasi diskon transaksi: dorong volume tanpa memakan margin berlebihan.';
        }

        // breakdown alokasi sederhana
        $absGap = abs($gap);
        $plan['allocation'] = $gap > 0 ? [['bucket' => 'Top Bundles/Associations', 'percent' => 50, 'nominal' => round($absGap * 0.5, 2)], ['bucket' => 'High-Margin SKUs', 'percent' => 30, 'nominal' => round($absGap * 0.3, 2)], ['bucket' => 'Promo/Marketing', 'percent' => 20, 'nominal' => round($absGap * 0.2, 2)]] : [['bucket' => 'Safety Stock Fast-Moving', 'percent' => 60, 'note' => '+10–15%'], ['bucket' => 'Eksperimen Bundle Promo', 'percent' => 25], ['bucket' => 'Cadangan Kas', 'percent' => 15]];

        return $plan;
    }
}
