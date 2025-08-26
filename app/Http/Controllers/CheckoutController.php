<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SalesTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckoutController
{
    public function index()
    {
        $cart = session('cart', []);

        if (empty($cart)) {
            return redirect()->route('customer.cart.index')->with('error', 'Keranjang kosong');
        }

        $productIds = array_keys($cart);
        $products = Product::query()->join('product_brands', 'products.product_brand_id', '=', 'product_brands.id')->select('products.id', 'products.name as product_name', 'product_brands.name as brand_name', 'selling_price', 'discount', 'image')->whereIn('products.id', $productIds)->get();

        $user = Auth::user();
        $customer = $user->customer;

        return view('customer.checkout.index', compact('products', 'cart', 'customer'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        $cart = session('cart', []);

        if (empty($cart)) {
            return redirect()->route('customer.cart.index')->with('error', 'Keranjang kosong');
        }

        $user = Auth::user();
        $customer = $user->customer;

        if (!$customer) {
            return redirect()->back()->with('error', 'Data customer tidak ditemukan');
        }

        $productIds = array_keys($cart);
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
        $total_amount = 0;
        foreach ($cart as $productIds => $quantity) {
            $product = $products->get($productIds);
            if ($product->discount > 0.00) {
                $price = $product->selling_price * $product->discount;
            } else {
                $price = $product->selling_price;
            }
            $productTotal = $price * $quantity;
            $total_amount += $productTotal;
        }

        DB::transaction(function () use ($customer, $cart, $products, $total_amount) {
            // Buat Purchase Order
            $po = PurchaseOrder::create([
                'customer_id' => $customer->id,
                'total_amount' => $total_amount,
                'order_date' => now(),
                'status' => 'pending',
            ]);

            // Buat Purchase Order Items
            foreach ($cart as $productId => $quantity) {
                $product = $products->get($productId);
                if ($product) {
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'product_id' => $productId,
                        'quantity' => $quantity,
                    ]);
                }
            }
        });

        // Kosongkan keranjang
        session()->forget('cart');

        return redirect()->route('customer.order.index')->with('success', 'Pesanan berhasil dibuat. Menunggu konfirmasi admin.');
    }
}
