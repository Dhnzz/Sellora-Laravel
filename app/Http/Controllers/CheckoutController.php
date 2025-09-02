<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
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
            if ($product->discount > 0.0) {
                $price = $product->selling_price * $product->discount;
            } else {
                $price = $product->selling_price;
            }
            $productTotal = $price * $quantity;
            $total_amount += $productTotal;
        }

        DB::transaction(function () use ($customer, $cart, $products, $total_amount) {
            // Generate invoice ID
            $invoiceDate = now();
            $invoiceDateForId = $invoiceDate->format('dmY');
            $todayCount = SalesTransaction::whereDate('invoice_date', $invoiceDate->format('Y-m-d'))->count() + 1;
            $invoiceId = 'INV-' . $invoiceDateForId . '-' . str_pad($todayCount, 4, '0', STR_PAD_LEFT);

            // Buat Sales Transaction langsung
            $salesTransaction = SalesTransaction::create([
                'customer_id' => $customer->id,
                'admin_id' => 1, // Default admin ID, bisa disesuaikan
                'sales_agent_id' => 1, // Default sales agent ID, bisa disesuaikan
                'order_date' => $invoiceDate->format('Y-m-d'),
                'invoice_id' => $invoiceId,
                'invoice_date' => $invoiceDate->format('Y-m-d'),
                'discount_percent' => 0, // Tidak ada diskon tambahan
                'initial_total_amount' => $total_amount,
                'final_total_amount' => $total_amount,
                'note' => 'Pesanan dari customer',
                'transaction_status' => 'process',
            ]);

            // Buat Sales Transaction Items
            foreach ($cart as $productId => $quantity) {
                $product = $products->get($productId);
                if ($product) {
                    $price = $product->discount > 0.0 ? $product->selling_price * $product->discount : $product->selling_price;

                    SalesTransactionItem::create([
                        'sales_transaction_id' => $salesTransaction->id,
                        'product_id' => $productId,
                        'quantity_ordered' => $quantity,
                        'quantity_sold' => $quantity,
                        'msu_price' => $product->selling_price,
                    ]);
                }
            }
        });

        // Kosongkan keranjang
        session()->forget('cart');

        return redirect()->route('customer.order.index')->with('success', 'Pesanan berhasil dibuat. Menunggu konfirmasi admin.');
    }
}
