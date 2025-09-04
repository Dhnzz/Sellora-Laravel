<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Customer;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use App\Models\ProductAssociation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RecommendationService
{
    public function getRecommendedProductsForCustomer(Customer|int $customer, int $limit = 10): Collection
    {
        // Normalisasi input customer
        if (is_int($customer)) {
            $customer = Customer::find($customer);
        }
        if (!$customer) {
            Log::info('RecommendationService: Customer tidak ditemukan.');
            return collect();
        }

        $limit = max(1, (int) $limit);

        // Ambil semua produk yang PERNAH dibeli customer (distinct)
        $purchasedProductIds = SalesTransaction::query()->where('customer_id', $customer->id)->join('sales_transaction_items as sti', 'sales_transactions.id', '=', 'sti.sales_transaction_id')->whereNotNull('sti.product_id')->distinct()->pluck('sti.product_id')->map(fn($v) => (int) $v)->values();

        if ($purchasedProductIds->isEmpty()) {
            Log::info('RecommendationService: Tidak ada histori pembelian untuk customer', ['customer_id' => $customer->id]);
            return collect();
        }

        // Ambil aturan: antecedent mengandung MINIMAL salah satu produk yang pernah dibeli.
        // -> gunakan whereJsonContains dalam 1 grup OR
        $ids = $purchasedProductIds;

        $rules = ProductAssociation::query()
        ->where(function ($q) use ($ids) {
            foreach ($ids as $id) {
                $q->orWhereJsonContains('atecedent_product_ids', $id);
            }
        })
        ->get(['atecedent_product_ids', 'consequent_product_ids', 'support', 'confidence', 'lift']);
        
        if ($rules->isEmpty()) {
            Log::info('RecommendationService: Tidak ada aturan asosiasi yang cocok', ['customer_id' => $customer->id]);
            return collect();
        }

        // Hitung skor rekomendasi untuk produk konsekuen YANG BELUM DIBELI
        $purchasedSet = $purchasedProductIds->flip(); // buat cek cepat
        $score = [];
        foreach ($rules as $rule) {
            $consequentIds = json_decode($rule->consequent_product_ids, true) ?: [];
            foreach ($consequentIds as $cid) {
                $cid = (int) $cid;
                // bobot: confidence dominan, lalu lift & support sebagai penguat
                $baseScore = (float) $rule->confidence + 0.1 * (float) $rule->lift + 0.05 * (float) $rule->support;
                if ($purchasedSet->has($cid)) {
                    // Jika sudah pernah dibeli, tambahkan skor sangat kecil agar tetap diurutkan paling bawah
                    $score[$cid] = ($score[$cid] ?? 0) + 0.00001 * $baseScore;
                } else {
                    $score[$cid] = ($score[$cid] ?? 0) + $baseScore;
                }
            }
        }

        if (empty($score)) {
            Log::info('RecommendationService: Tidak ada produk yang bisa direkomendasikan', ['customer_id' => $customer->id]);
            return collect();
        }

        // Urutkan & ambil top-N
        arsort($score);
        $recommendedIds = collect(array_keys($score))->map(fn($v) => (int) $v)->take($limit)->values();

        // OrderByRaw FIELD agar urutan sesuai ranking
        $idsCsv = $recommendedIds->implode(',');
        $products = Product::query()->select('id', 'name', 'selling_price', 'discount', 'image')->whereIn('id', $recommendedIds)->when($recommendedIds->isNotEmpty(), fn($q) => $q->orderByRaw("FIELD(id, {$idsCsv})"))->get();

        if ($products->isEmpty()) {
            Log::info('RecommendationService: Produk hasil rekomendasi tidak ditemukan di DB', [
                'customer_id' => $customer->id,
                'ids' => $recommendedIds->all(),
            ]);
        }

        return $products;
    }
}
