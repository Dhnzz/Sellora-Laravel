<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductBrand;
use Illuminate\Http\Request;
use App\Models\ProductBundle;
use App\Services\ML\FpClient;
use App\Models\SalesTransaction;
use App\Models\ProductAssociation;
use App\Models\SalesTransactionItem;
use Illuminate\Support\Facades\Auth;
use App\Models\ProductAssociationCustomer;
use App\Services\RecommendationService;

class ShopController
{
    public function home()
    {
        $today = now()->toDateString();

        $bundles = ProductBundle::query()->where('is_active', true)->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->select('id', 'bundle_name', 'description', 'special_bundle_price', 'original_price', 'start_date', 'end_date', 'flyer')->orderBy('start_date')->limit(8)->get();
        $discountProducts = Product::query()->whereNot('discount', '<=', 0.0)->get();

        // Rekomendasi: delegasikan ke service agar function tetap ringkas
        $recommendedProducts = collect();
        $user = Auth::user();
        if ($user && isset($user->customer)) {
            $recommendedProducts = app(RecommendationService::class)->getRecommendedProductsForCustomer($user->customer, 12);
        }

        return view('customer.home', compact('bundles', 'discountProducts', 'recommendedProducts'));
    }

    public function catalog(Request $request)
    {
        $user = Auth::user();
        // Gunakan service rekomendasi yang sama seperti di home
        $recommendedIds = collect();
        if ($user && isset($user->customer)) {
            $recoProducts = app(RecommendationService::class)->getRecommendedProductsForCustomer($user->customer, 200);
            $recommendedIds = $recoProducts->pluck('id');
        }

        // 3) query produk: recommended dulu (urutan custom), lanjut sisanya (terbaru)
        $baseQ = Product::query()->join('product_brands', 'products.product_brand_id', '=', 'product_brands.id')->select('products.id', 'products.name as product_name', 'product_brands.name as brand_name', 'selling_price', 'discount', 'image', 'product_brand_id', 'products.created_at');

        // optional filter (brand, q search, dsb.)
        if ($request->filled('q')) {
            $q = $request->q;
            $baseQ->where('products.name', 'like', "%{$q}%")->orWhere('product_brands.name', 'like', "%{$q}%");
        }

        // filter brand
        if ($request->filled('brand') && $request->brand !== 'all') {
            $baseQ->where('product_brands.name', 'like', "%{$request->brand}%");
        }

        if ($request->filled('sortBy') && $request->sortBy !== '') {
            switch ($request->sortBy) {
                case 'recommended':
                    // kalau tidak ada histori: default terbaru
                    if ($recommendedIds->isEmpty()) {
                        $baseQ->orderByDesc('created_at');
                        // $brands = ProductBrand::select('id', 'name')->orderBy('name')->get();
                        // $selectedBrand = $request->brand_id ?? 'all';
                        // return view('customer.catalog', compact('products', 'brands', 'selectedBrand'));
                    } else {
                        // ada rekomendasi → bikin CASE WHEN: yg termasuk rekomendasi ranking=0, lainnya=1
                        // lalu untuk yang ranking=0, jaga urutan pakai FIELD(id, list...)
                        $recommendedList = $recommendedIds->implode(',');
                        $baseQ
                            ->orderByRaw("CASE WHEN products.id IN ({$recommendedList}) THEN 0 ELSE 1 END ASC")
                            ->orderByRaw("FIELD(products.id, {$recommendedList})") // urut sesuai skor
                            ->orderByDesc('created_at');
                    }

                    break;
                case 'lowestPrice':
                    $baseQ->orderBy('selling_price', 'asc');
                    break;
                case 'highestPrice':
                    $baseQ->orderByDesc('selling_price');
                    break;
                default:
                    $baseQ->orderBy('product_name', 'asc');
                    break;
            }
        }

        $products = $baseQ->paginate(20)->withQueryString();

        $brands = ProductBrand::select('id', 'name')->orderBy('name')->get();
        $selectedBrand = $request->brand ?? 'all';
        return view('customer.catalog', compact('products', 'brands', 'selectedBrand'));
    }
}
