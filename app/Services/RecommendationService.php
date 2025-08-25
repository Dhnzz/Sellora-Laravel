<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Customer;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use App\Models\ProductAssociationCustomer;
use App\Models\ProductAssociation;
use App\Services\ML\FpClient;
use Illuminate\Support\Collection;

class RecommendationService
{
    public function recomputeAssociationsForCustomer(Customer $customer): void
    {
        \Log::info('Recompute FP-Growth per-customer: start', ['customer_id' => $customer->id]);
        $transactions = SalesTransaction::query()
            ->join('purchase_orders', 'purchase_orders.id', '=', 'sales_transactions.purchase_order_id')
            ->where('purchase_orders.customer_id', $customer->id)
            ->with(['sales_transaction_items'])
            ->get(['sales_transactions.id', 'sales_transactions.invoice_id']);

        if ($transactions->isEmpty()) {
            \Log::info('Recompute FP-Growth per-customer: no transactions for customer', ['customer_id' => $customer->id]);
            ProductAssociationCustomer::where('customer_id', $customer->id)->delete();
            return;
        }

        $items = SalesTransactionItem::query()
            ->whereIn('sales_transaction_id', $transactions->pluck('id'))
            ->get(['sales_transaction_id', 'product_id'])
            ->groupBy('sales_transaction_id');

        $payload = [];
        foreach ($transactions as $tx) {
            $list = ($items[$tx->id] ?? collect())->pluck('product_id')->filter()->unique()->values()->map(fn($v) => (int) $v)->toArray();
            // Hanya masukkan basket dengan minimal 2 item agar FP-Growth bisa membentuk aturan
            if (count($list) >= 2) {
                $payload[$tx->invoice_id] = $list;
            }
        }

        ProductAssociationCustomer::where('customer_id', $customer->id)->delete();

        if (empty($payload)) {
            \Log::info('Recompute FP-Growth per-customer: empty payload after grouping items', ['customer_id' => $customer->id]);
            return;
        }

        $fp = app(FpClient::class);
        // Turunkan ambang agar peluang terbentuk aturan meningkat pada data kecil
        $result = $fp->mineRules($payload, 0.01, 0.2, 200, 'confidence');
        $rules = $result['rules'] ?? [];

        \Log::info('Recompute FP-Growth per-customer: rules generated', [
            'customer_id' => $customer->id,
            'payload_tx' => count($payload),
            'rules_count' => count($rules),
        ]);
        if (!empty($rules)) {
            foreach ($rules as $assoc) {
                ProductAssociationCustomer::create([
                    'customer_id' => $customer->id,
                    'atecedent_product_ids' => json_encode($assoc['antecedent_ids'] ?? []),
                    'consequent_product_ids' => json_encode($assoc['consequent_ids'] ?? []),
                    'support' => $assoc['support'] ?? 0,
                    'confidence' => $assoc['confidence'] ?? 0,
                    'lift' => $assoc['lift'] ?? 0,
                    'analysis_date' => now()->toDateString(),
                ]);
            }
        } else {
            // Fallback: gunakan aturan global yang relevan dengan histori customer
            $purchasedIds = collect($payload)->flatten()->unique()->values();

            if ($purchasedIds->isNotEmpty()) {
                $globalQ = ProductAssociation::query();
                foreach ($purchasedIds as $pid) {
                    $globalQ->orWhere('atecedent_product_ids', 'like', '%"' . (int) $pid . '"%');
                }
                $globalRules = $globalQ->get(['atecedent_product_ids', 'consequent_product_ids', 'support', 'confidence', 'lift']);

                \Log::info('Recompute FP-Growth fallback to global rules', [
                    'customer_id' => $customer->id,
                    'global_rules_count' => $globalRules->count(),
                ]);

                foreach ($globalRules as $gr) {
                    ProductAssociationCustomer::create([
                        'customer_id' => $customer->id,
                        'atecedent_product_ids' => $gr->atecedent_product_ids,
                        'consequent_product_ids' => $gr->consequent_product_ids,
                        'support' => (float) $gr->support,
                        'confidence' => (float) $gr->confidence,
                        'lift' => (float) $gr->lift,
                        'analysis_date' => now()->toDateString(),
                    ]);
                }
            }
        }

        \Log::info('Recompute FP-Growth per-customer: saved to DB', ['customer_id' => $customer->id]);
    }

    public function getRecommendedProductsForCustomer(Customer|int $customer, int $limit = 12): Collection
    {
        if (is_int($customer)) {
            $customer = Customer::find($customer);
        }
        if (!$customer) {
            return collect();
        }
        // Ambil asosiasi yang sudah tersimpan untuk customer tersebut
        $assocs = ProductAssociationCustomer::query()
            ->where('customer_id', $customer->id)
            ->get(['consequent_product_ids', 'confidence', 'lift']);

        // Skor rekomendasi dari aturan yang ada (tanpa analisis ulang)
        $score = [];
        foreach ($assocs as $a) {
            $conseq = json_decode($a->consequent_product_ids, true) ?: [];
            foreach ($conseq as $cid) {
                $cid = (int) $cid;
                $score[$cid] = ($score[$cid] ?? 0) + (float) $a->confidence + 0.1 * (float) $a->lift;
            }
        }

        if (empty($score)) {
            return collect();
        }

        arsort($score);
        $recommendedIds = collect(array_keys($score))->map(fn($v) => (int) $v)->take($limit);
        $idsCsv = $recommendedIds->implode(',');

        $recommendProducts = Product::query()
            ->select('id', 'name', 'selling_price', 'discount', 'image')
            ->whereIn('id', $recommendedIds)
            ->orderByRaw("FIELD(id, {$idsCsv})")
            ->get();
        return $recommendProducts;
    }
}
