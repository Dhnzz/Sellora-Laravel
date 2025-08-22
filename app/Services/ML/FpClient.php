<?php
namespace App\Services\ML;
use Illuminate\Support\Facades\Http;

class FpClient
{
    public function mineRules(array $transactions, float $minSup = 0.02, float $minConf = 0.3, int $topK = 50, string $metric = 'confidence'): array
    {
        $url = rtrim(config('prediction.flask_url'), '/') . '/fp/rules';
        $req = Http::timeout(60);
        if ($t = config('prediction.fp_token')) {
            $req = $req->withHeaders(['X-API-KEY' => $t]);
        }

        $res = $req->post($url, [
            'transactions' => $transactions,
            'min_support' => $minSup,
            'min_confidence' => $minConf,
            'top_k' => $topK,
            'metric' => $metric,
        ]);
        if (!$res->ok()) {
            throw new \RuntimeException('FP API error: ' . $res->status() . ' ' . $res->body());
        }
        return $res->json();
    }
}
