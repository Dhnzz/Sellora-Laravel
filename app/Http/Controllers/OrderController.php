<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PurchaseOrder;
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
            $query = PurchaseOrder::where('customer_id', $customer->id)->with(['purchase_order_items.product.product_brand']);

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Filter by period
            if ($request->filled('period')) {
                $days = (int) $request->period;
                $query->where('order_date', '>=', Carbon::now()->subDays($days));
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
                    $query->orderBy('order_date', 'asc');
                    break;
                case 'highest':
                    $query->orderBy('total_amount', 'desc');
                    break;
                case 'lowest':
                    $query->orderBy('total_amount', 'asc');
                    break;
                default:
                    $query->orderBy('order_date', 'desc');
                    break;
            }

            // Pagination
            $perPage = 10;
            $orders = $query->paginate($perPage);

            // Transform data untuk response
            $transformedOrders = $orders->getCollection()->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_date' => $order->order_date,
                    'status' => $order->status,
                    'total_amount' => $order->total_amount,
                    'shipping_address' => $order->customer->address ?? null,
                    'created_at' => $order->created_at,
                    'customer' => [
                        'name' => $order->customer->name ?? 'Unknown',
                        'email' => $order->customer->user->email ?? 'Unknown',
                        'phone' => $order->customer->phone ?? null,
                    ],
                    'orderItems' => $order->purchase_order_items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product' => [
                                'name' => $item->product->name ?? 'Unknown Product',
                                'discount' => $item->product->discount ?? null,
                                'image' => $item->product->image ?? null,
                                'brand' => [
                                    'name' => $item->product->product_brand->name ?? 'No Brand',
                                ],
                            ],
                            'quantity' => $item->quantity,
                            'price' => $item->product->selling_price,
                            'net_price' => $item->product->discount > 0.0 ? $item->product->selling_price * $item->product->discount : $item->product->selling_price,
                            'subtotal' => $item->quantity * $item->product->selling_price,
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

            $order = PurchaseOrder::where('id', $id)
                ->where('customer_id', $customer->id)
                ->with(['customer.user', 'purchase_order_items.product.product_brand'])
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
                'order_date' => $order->order_date,
                'status' => $order->status,
                'total_amount' => $order->total_amount,
                'shipping_address' => $order->customer->address ?? null,
                'created_at' => $order->created_at,
                'customer' => (object) [
                    'name' => $order->customer->name ?? 'Unknown',
                    'email' => $order->customer->user->email ?? 'Unknown',
                    'phone' => $order->customer->phone ?? null,
                ],
                'orderItems' => $order->purchase_order_items->map(function ($item) {
                    $net_price = $item->product->discount > 0.0 ? $item->product->selling_price * $item->product->discount : $item->product->selling_price;
                    return (object) [
                        'id' => $item->id,
                        'product' => (object) [
                            'name' => $item->product->name ?? 'Unknown Product',
                            'discount' => $item->product->discount ?? null,
                            'image' => $item->product->image ?? null,
                            'brand' => (object) [
                                'name' => $item->product->product_brand->name ?? 'No Brand',
                            ],
                        ],
                        'quantity' => $item->quantity,
                        'price' => $item->product->selling_price,
                        'net_price' => $net_price,
                        'subtotal' => $item->quantity * $net_price,
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
