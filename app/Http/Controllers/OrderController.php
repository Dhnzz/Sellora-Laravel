<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\SalesTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class OrderController
{
    public function index()
    {
        return view('customer.order.index');
    }

    public function data(Request $request)
    {
        try {
            $user = Auth::user();
            $customer = $user->customer;

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer tidak ditemukan',
                ]);
            }

            // Base query untuk order customer melalui purchase_order
            $query = SalesTransaction::whereHas('purchase_order', function ($q) use ($customer) {
                $q->where('customer_id', $customer->id);
            })
                ->with(['purchase_order.customer', 'sales_transaction_items.product.product_brand'])
                ->orderBy('created_at', 'desc');

            // Filter by status
            if ($request->filled('status')) {
                $query->where('transaction_status', $request->status);
            }

            // Filter by period
            if ($request->filled('period')) {
                $days = (int) $request->period;
                $query->where('invoice_date', '>=', Carbon::now()->subDays($days));
            }

            // Search
            // if ($request->filled('search')) {
            //     $search = $request->search;
            //     $query->where(function ($q) use ($search) {
            //         $q->where('invoice_id', 'like', "%{$search}%")
            //         ->orWhere('transaction_status', 'like', "%{$search}%")
            //         ->orWhere('transaction_status', 'like', "%{$search}%");
            //     });
            // }

            // Sort
            switch ($request->get('sort', 'latest')) {
                case 'oldest':
                    $query->orderBy('invoice_date', 'asc');
                    break;
                case 'highest':
                    $query->orderBy('final_total_amount', 'desc');
                    break;
                case 'lowest':
                    $query->orderBy('final_total_amount', 'asc');
                    break;
                default:
                    $query->orderBy('invoice_date', 'desc');
                    break;
            }

            // Pagination
            $perPage = 10;
            $orders = $query->paginate($perPage);

            // Transform data untuk response
            $transformedOrders = $orders->getCollection()->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->invoice_id,
                    'order_date' => $order->invoice_date,
                    'status' => $order->transaction_status,
                    'total_amount' => $order->final_total_amount,
                    'subtotal' => $order->initial_total_amount,
                    'discount_amount' => $order->initial_total_amount - $order->final_total_amount,
                    'shipping_address' => $order->purchase_order->customer->address ?? null,
                    'created_at' => $order->created_at,
                    'payment_confirmed_at' => $order->transaction_status === 'success' ? $order->delivery_confirmed_at : null,
                    'processing_at' => $order->created_at,
                    'shipped_at' => $order->delivery_confirmed_at,
                    'delivered_at' => $order->delivery_confirmed_at,
                    'customer' => [
                        'name' => $order->purchase_order->customer->name ?? 'Unknown',
                        'email' => $order->purchase_order->customer->user->email ?? 'Unknown',
                        'phone' => $order->purchase_order->customer->phone ?? null,
                    ],
                    'orderItems' => $order->sales_transaction_items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product' => [
                                'name' => $item->product->name ?? 'Unknown Product',
                                'image' => $item->product->image ?? null,
                                'brand' => [
                                    'name' => $item->product->brand->name ?? 'No Brand',
                                ],
                            ],
                            'quantity' => $item->quantity_sold,
                            'price' => $item->msu_price,
                            'subtotal' => $item->quantity_sold * $item->msu_price,
                        ];
                    }),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => $transformedOrders,
                    'pagination' => [
                        'current_page' => $orders->currentPage(),
                        'last_page' => $orders->lastPage(),
                        'per_page' => $orders->perPage(),
                        'total' => $orders->total(),
                        'from' => $orders->firstItem(),
                        'to' => $orders->lastItem(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            $customer = $user->customer;

            if (!$customer) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Customer tidak ditemukan',
                    ],
                    404,
                );
            }

            $order = SalesTransaction::where('id', $id)
                ->whereHas('purchase_order', function ($q) use ($customer) {
                    $q->where('customer_id', $customer->id);
                })
                ->with(['purchase_order.customer.user', 'sales_transaction_items.product.brand'])
                ->first();

            if (!$order) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Order tidak ditemukan',
                    ],
                    404,
                );
            }

            // Transform order data
            $orderData = (object) [
                'id' => $order->id,
                'order_number' => $order->invoice_id,
                'status' => $order->transaction_status,
                'total_amount' => $order->final_total_amount,
                'subtotal' => $order->initial_total_amount,
                'shipping_cost' => 0, // Sesuaikan dengan field yang ada
                'discount_amount' => $order->initial_total_amount - $order->final_total_amount,
                'payment_method' => 'Transfer Bank', // Default payment method
                'payment_status' => $order->transaction_status === 'success' ? 'paid' : 'pending',
                'payment_proof' => null, // Tidak ada field payment_proof di model
                'shipping_address' => $order->purchase_order->customer->address ?? null,
                'created_at' => $order->created_at,
                'payment_confirmed_at' => $order->transaction_status === 'success' ? $order->delivery_confirmed_at : null,
                'processing_at' => $order->created_at,
                'shipped_at' => $order->delivery_confirmed_at,
                'delivered_at' => $order->delivery_confirmed_at,
                'customer' => (object) [
                    'name' => $order->purchase_order->customer->name ?? 'Unknown',
                    'email' => $order->purchase_order->customer->user->email ?? 'Unknown',
                    'phone' => $order->purchase_order->customer->phone ?? null,
                ],
                'orderItems' => $order->sales_transaction_items->map(function ($item) {
                    return (object) [
                        'id' => $item->id,
                        'product' => (object) [
                            'name' => $item->product->name ?? 'Unknown Product',
                            'image' => $item->product->image ?? null,
                            'brand' => (object) [
                                'name' => $item->product->brand->name ?? 'No Brand',
                            ],
                        ],
                        'quantity' => $item->quantity_sold,
                        'price' => $item->msu_price,
                        'subtotal' => $item->quantity_sold * $item->msu_price,
                    ];
                }),
            ];

            $html = view('customer.order.detail', compact('orderData'))->render();

            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }
}
