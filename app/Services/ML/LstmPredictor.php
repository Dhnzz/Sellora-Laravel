<?php

namespace App\Services\ML;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class LstmPredictor
{
    public function predictNext(array $rows, int $lookBack = 6): array
    {
        // $rows: [ ['date'=>'YYYY-MM-01','value'=>float], ... ] (ASC)
        if (count($rows) < $lookBack) {
            throw new \InvalidArgumentException("Butuh minimal {$lookBack} bulan data.");
        }

        // ambil hanya ANGKA
        $values = array_map(fn($r) => (float) (is_array($r) ? $r['value'] : $r), $rows);
        $lastLookback = array_slice($values, -$lookBack);

        $payload = [
            'series' => array_values($lastLookback),
            'look_back' => $lookBack,
        ];

        $cfg = config('services.recsys');
        if (!empty($cfg['lstm_norm_min']) && !empty($cfg['lstm_norm_max'])) {
            $payload['norm'] = [
                'min' => (float) $cfg['lstm_norm_min'],
                'max' => (float) $cfg['lstm_norm_max'],
            ];
        }

        // --- DEBUG sementara (aktifkan jika perlu)
        // \Log::info('LSTM payload to Flask', $payload);

        $req = Http::timeout(12);
        if (!empty($cfg['lstm_token'])) {
            $req = $req->withHeaders(['X-PRED-TOKEN' => $cfg['lstm_token']]);
        }

        $res = $req->post(rtrim($cfg['lstm_url'], '/') . '/predict', $payload);

        if (!$res->ok()) {
            \Log::error('Flask 500 body: ' . $res->body());
            throw new \RuntimeException('Flask error ' . $res->status());
        }

        $json = $res->json();
        $pred = (float) ($json['predicted_profit'] ?? 0.0);
        $lastDate = end($rows)['date'];
        $next = Carbon::parse($lastDate)->startOfMonth()->addMonthNoOverflow();
        return [
            'next_year' => $next->year,
            'next_month' => $next->month,
            'predicted_profit' => $pred,
            'meta' => [
                'last_actual' => (float) end($values),
                'scaled_prediction' => $json['scaled_prediction'] ?? null,
                'used_min' => $json['used_min'] ?? null,
                'used_max' => $json['used_max'] ?? null,
                'model_version' => $json['model_version'] ?? ($cfg['lstm_model_version'] ?? 'lstm_v1'),
            ],
        ];
    }
}
