<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductBrand;
use Illuminate\Http\Request;
use App\Models\ProductBundle;
use App\Models\SalesTransaction;
use App\Models\ProductAssociation;
use Illuminate\Support\Facades\Auth;

class ShopController
{
    public function home()
    {
        $today = now()->toDateString();

        $bundles = ProductBundle::query()->where('is_active', true)->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->select('id', 'bundle_name', 'description', 'special_bundle_price', 'original_price', 'start_date', 'end_date', 'flyer')->orderBy('start_date')->limit(8)->get();

        return view('customer.page.home', compact('bundles'));
    }

    public function catalog(Request $request)
    {
        $user = Auth::user();

        // 1) cari produk yang pernah dibeli customer ini
        // asumsi relasi: sales_transactions -> punya customer_user_id
        $purchasedIds = SalesTransaction::query()->where('customer_user_id', $user->id)->join('sales_transaction_items', 'sales_transaction_items.sales_transaction_id', '=', 'sales_transactions.id')->pluck('sales_transaction_items.product_id')->unique()->values();

        // 2) kalau ada histori → hitung skor rekomendasi pakai ProductAssociation
        $recommendedIds = collect();

        if ($purchasedIds->isNotEmpty()) {
            // ambil asosiasi yang antecedent mengandung salah satu dari produk yang dibeli
            $assocsQ = ProductAssociation::query();
            foreach ($purchasedIds as $pid) {
                $assocsQ->orWhereJsonContains('atecedent_product_ids', (int) $pid); // kolom 'atecedent...' mengikuti schema kamu
            }
            $assocs = $assocsQ->get(['consequent_product_ids', 'confidence', 'lift']);

            // agregasi skor
            $score = [];
            foreach ($assocs as $a) {
                $conseq = json_decode($a->consequent_product_ids, true) ?: [];
                foreach ($conseq as $cid) {
                    // jangan rekomendasikan produk yg sudah pernah dibeli? (opsional) — di sini tetap direkomendasikan
                    $score[$cid] = ($score[$cid] ?? 0) + (float) $a->confidence + 0.1 * (float) $a->lift;
                }
            }

            if (!empty($score)) {
                arsort($score); // desc
                $recommendedIds = collect(array_keys($score))->map(fn($v) => (int) $v);
            }
        }

        // 3) query produk: recommended dulu (urutan custom), lanjut sisanya (terbaru)
        $baseQ = Product::query()->select('id', 'name', 'selling_price', 'created_at');

        // optional filter (brand, q search, dsb.)
        if ($request->filled('q')) {
            $q = $request->q;
            $baseQ->where('name', 'like', "%{$q}%");
        }

        // kalau tidak ada histori: default terbaru
        if ($recommendedIds->isEmpty()) {
            $products = $baseQ->orderByDesc('created_at')->paginate(24)->withQueryString();
            return view('customer.catalog', compact('products'));
        }

        // ada rekomendasi → bikin CASE WHEN: yg termasuk rekomendasi ranking=0, lainnya=1
        // lalu untuk yang ranking=0, jaga urutan pakai FIELD(id, list...)
        $recommendedList = $recommendedIds->implode(',');
        $products = $baseQ
            ->orderByRaw("CASE WHEN id IN ({$recommendedList}) THEN 0 ELSE 1 END ASC")
            ->orderByRaw("FIELD(id, {$recommendedList})") // urut sesuai skor
            ->orderByDesc('created_at') // sisanya by newest
            ->paginate(24)
            ->withQueryString();

        return view('customer.catalog', compact('products'));
    }
}
