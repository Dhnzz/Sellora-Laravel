<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController
{
    public function index()
    {
        $cart = session('cart', []);
        $products = collect();

        if (!empty($cart)) {
            $productIds = array_keys($cart);
            $products = Product::query()->join('product_brands', 'products.product_brand_id', '=', 'product_brands.id')->select('products.id', 'products.name as product_name', 'product_brands.name as brand_name', 'selling_price', 'discount', 'image')->whereIn('products.id', $productIds)->get();
        }

        return view('customer.cart.index', compact('products', 'cart'));
    }

    public function getCartData()
    {
        $cart = session('cart', []);
        $products = collect();
        $subtotal = 0;
        $totalDiscount = 0;
        $totalItems = 0;
        $initialPrice = 0;

        if (!empty($cart)) {
            $productIds = array_keys($cart);
            $products = Product::query()->join('product_brands', 'products.product_brand_id', '=', 'product_brands.id')->select('products.id', 'products.name as product_name', 'product_brands.name as brand_name', 'selling_price', 'discount', 'image')->whereIn('products.id', $productIds)->get();

            foreach ($products as $product) {
                $quantity = $cart[$product->id] ?? 0;
                $originalPrice = $product->selling_price * $quantity;
                $finalPrice = $product->discount > 0.00 ? ($product->selling_price - ($product->selling_price * $product->discount)) * $quantity : $originalPrice;

                $subtotal += $finalPrice;
                $totalDiscount += $originalPrice - $finalPrice;
                $totalItems += $quantity;
                $initialPrice += $originalPrice;
            }
        }

        return response()->json([
            'success' => true,
            'products' => $products,
            'cart' => $cart,
            'initialPrice' => $initialPrice,
            'subtotal' => $subtotal,
            'totalDiscount' => $totalDiscount,
            'totalItems' => $totalItems,
            'cart_count' => array_sum($cart),
        ]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = session('cart', []);
        $productId = $request->product_id;

        if (isset($cart[$productId])) {
            $cart[$productId] += $request->quantity;
        } else {
            $cart[$productId] = $request->quantity;
        }

        session(['cart' => $cart]);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan ke keranjang',
            'cart_count' => array_sum($cart),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:0',
        ]);

        $cart = session('cart', []);
        $productId = $request->product_id;

        if ($request->quantity == 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = $request->quantity;
        }

        session(['cart' => $cart]);

        return response()->json([
            'success' => true,
            'message' => 'Keranjang berhasil diperbarui',
            'cart_count' => array_sum($cart),
        ]);
    }

    public function remove(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $cart = session('cart', []);
        unset($cart[$request->product_id]);
        session(['cart' => $cart]);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dihapus dari keranjang',
            'cart_count' => array_sum($cart),
        ]);
    }

    public function clear()
    {
        session()->forget('cart');

        return response()->json([
            'success' => true,
            'message' => 'Keranjang berhasil dikosongkan',
        ]);
    }
}
