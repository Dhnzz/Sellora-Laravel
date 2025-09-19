<?php
namespace App\Services\ML;
use Illuminate\Support\Facades\Http;

class FpClient
{
    public function mineRules(array $transactions, float $minSup = 0.06, float $minLift = 2, int $topK = 50, string $metric = 'lift'): array
    {
        $url = rtrim(config('prediction.flask_url'), '/') . '/fp/rules';
        $req = Http::timeout(60);
        if ($t = config('prediction.fp_token')) {
            $req = $req->withHeaders(['X-API-KEY' => $t]);
        }

        $res = $req->post($url, [
            'transactions' => $transactions,
            'min_support' => $minSup,
            'min_lift' => $minLift,
            'top_k' => $topK,
            'metric' => $metric,
        ]);
        if (!$res->ok()) {
            throw new \RuntimeException('FP API error: ' . $res->status() . ' ' . $res->body());
        }
        return $res->json();
    }
}
