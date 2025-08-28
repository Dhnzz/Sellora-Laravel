<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\SalesTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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

            $status = $request->get('status');
            $periodDays = $request->filled('period') ? (int) $request->get('period') : null;
            $sort = $request->get('sort', 'latest');
            $perPage = (int) $request->get('per_page', 10);
            $currentPage = (int) $request->get('page', 1);

            // Query Purchase Orders (belum tentu ada SalesTransaction)
            $poQuery = PurchaseOrder::where('customer_id', $customer->id)->with(['customer.user', 'purchase_order_items.product.product_brand']);

            if ($status) {
                $poQuery->where('status', $status);
            }
            if ($periodDays) {
                $poQuery->where('order_date', '>=', Carbon::now()->subDays($periodDays));
            }

            // Query Sales Transactions (yang sudah dikonfirmasi admin)
            $stQuery = SalesTransaction::whereHas('purchase_order', function ($q) use ($customer) {
                $q->where('customer_id', $customer->id);
            })->with(['purchase_order.customer.user', 'sales_transaction_items.product.product_brand']);

            if ($status) {
                $stQuery->where('transaction_status', $status);
            }
            if ($periodDays) {
                $stQuery->where('invoice_date', '>=', Carbon::now()->subDays($periodDays));
            }

            // Ambil data mentah (tanpa paginate), lalu normalisasi struktur
            $poList = $poQuery->get()->map(function ($order) {
                return [
                    'id' => $order->id,
                    'type' => 'po',
                    'order_number' => null,
                    'order_date' => $order->order_date,
                    'status' => $order->status,
                    'total_amount' => $order->total_amount,
                    'subtotal' => null,
                    'shipping_address' => $order->customer->address ?? null,
                    'created_at' => $order->created_at,
                    'customer' => [
                        'name' => $order->customer->name ?? 'Unknown',
                        'email' => $order->customer->user->email ?? 'Unknown',
                        'phone' => $order->customer->phone ?? null,
                    ],
                    'orderItems' => $order->purchase_order_items->map(function ($item) {
                        $netPrice = ($item->product->discount ?? 0.0) > 0.0 ? $item->product->selling_price * $item->product->discount : $item->product->selling_price;
                        return [
                            'id' => $item->id,
                            'product' => [
                                'name' => $item->product->name ?? 'Unknown Product',
                                'discount' => $item->product->discount ?? null,
                                'image' => $item->product->image ?? null,
                                'brand' => [
                                    'name' => optional($item->product->product_brand)->name ?? 'No Brand',
                                ],
                            ],
                            'quantity' => $item->quantity,
                            'price' => $item->product->selling_price,
                            'net_price' => $netPrice,
                            'subtotal' => $item->quantity * $netPrice,
                        ];
                    }),
                ];
            });

            $stList = $stQuery->get()->map(function ($order) {
                return [
                    'id' => $order->id,
                    'type' => 'st',
                    'order_number' => $order->invoice_id,
                    'order_date' => $order->invoice_date,
                    'status' => $order->transaction_status,
                    'total_amount' => $order->final_total_amount,
                    'subtotal' => $order->initial_total_amount,
                    'shipping_address' => optional($order->purchase_order->customer)->address ?? null,
                    'created_at' => $order->created_at,
                    'payment_confirmed_at' => $order->transaction_status === 'success' ? $order->delivery_confirmed_at : null,
                    'processing_at' => $order->created_at,
                    'shipped_at' => $order->delivery_confirmed_at,
                    'delivered_at' => $order->delivery_confirmed_at,
                    'customer' => [
                        'name' => optional($order->purchase_order->customer)->name ?? 'Unknown',
                        'email' => optional(optional($order->purchase_order->customer)->user)->email ?? 'Unknown',
                        'phone' => optional($order->purchase_order->customer)->phone ?? null,
                    ],
                    'orderItems' => $order->sales_transaction_items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product' => [
                                'name' => $item->product->name ?? 'Unknown Product',
                                'image' => $item->product->image ?? null,
                                'brand' => [
                                    'name' => optional($item->product->brand)->name ?? 'No Brand',
                                ],
                            ],
                            'quantity' => $item->quantity_sold,
                            'price' => $item->msu_price,
                            'subtotal' => $item->quantity_sold * $item->msu_price,
                        ];
                    }),
                ];
            });

            // Gabungkan
            $all = $poList->concat($stList);

            // Sorting gabungan
            $sorted = $all
                ->sort(function ($a, $b) use ($sort) {
                    switch ($sort) {
                        case 'oldest':
                            return strtotime($a['order_date']) <=> strtotime($b['order_date']);
                        case 'highest':
                            return $b['total_amount'] <=> $a['total_amount'];
                        case 'lowest':
                            return $a['total_amount'] <=> $b['total_amount'];
                        case 'latest':
                        default:
                            return strtotime($b['order_date']) <=> strtotime($a['order_date']);
                    }
                })
                ->values();

            // Pagination manual
            $total = $sorted->count();
            $items = $sorted->slice(($currentPage - 1) * $perPage, $perPage)->values();
            $paginator = new LengthAwarePaginator($items, $total, $perPage, $currentPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => $items,
                    'pagination' => [
                        'current_page' => $paginator->currentPage(),
                        'last_page' => $paginator->lastPage(),
                        'per_page' => $paginator->perPage(),
                        'total' => $paginator->total(),
                        'from' => $total === 0 ? null : ($currentPage - 1) * $perPage + 1,
                        'to' => $total === 0 ? null : ($currentPage - 1) * $perPage + $items->count(),
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

            // Coba ambil type dari query (?type=po|st). Jika tidak ada, fallback: cari PO dulu, lalu ST.
            $type = request()->query('type');
            $orderData = null;

            if ($type === 'po' || !$type) {
                $po = PurchaseOrder::where('id', $id)
                    ->where('customer_id', $customer->id)
                    ->with(['customer.user', 'purchase_order_items.product.product_brand'])
                    ->first();

                if ($po) {
                    $orderData = (object) [
                        'id' => $po->id,
                        'order_number' => null,
                        'order_date' => $po->order_date,
                        'status' => $po->status,
                        'total_amount' => $po->total_amount,
                        'subtotal' => null,
                        'shipping_cost' => 0,
                        'discount_amount' => 0,
                        'payment_method' => null,
                        'payment_status' => null,
                        'payment_proof' => null,
                        'shipping_address' => $po->customer->address ?? null,
                        'created_at' => $po->created_at,
                        'payment_confirmed_at' => null,
                        'processing_at' => $po->created_at,
                        'shipped_at' => null,
                        'delivered_at' => null,
                        'customer' => (object) [
                            'name' => $po->customer->name ?? 'Unknown',
                            'email' => optional($po->customer->user)->email ?? 'Unknown',
                            'phone' => $po->customer->phone ?? null,
                        ],
                        'orderItems' => $po->purchase_order_items->map(function ($item) {
                            $netPrice = ($item->product->discount ?? 0.0) > 0.0 ? $item->product->selling_price * $item->product->discount : $item->product->selling_price;
                            return (object) [
                                'id' => $item->id,
                                'product' => (object) [
                                    'name' => $item->product->name ?? 'Unknown Product',
                                    'discount' => $item->product->discount ?? null,
                                    'image' => $item->product->image ?? null,
                                    'brand' => (object) [
                                        'name' => optional($item->product->product_brand)->name ?? 'No Brand',
                                    ],
                                ],
                                'quantity' => $item->quantity,
                                'price' => $item->product->selling_price,
                                'net_price' => $netPrice,
                                'subtotal' => $item->quantity * $netPrice,
                            ];
                        }),
                    ];
                }
            }

            if (!$orderData && ($type === 'st' || !$type)) {
                $st = SalesTransaction::where('id', $id)
                    ->whereHas('purchase_order', function ($q) use ($customer) {
                        $q->where('customer_id', $customer->id);
                    })
                    ->with(['purchase_order.customer.user', 'sales_transaction_items.product.brand'])
                    ->first();

                if ($st) {
                    $orderData = (object) [
                        'id' => $st->id,
                        'order_number' => $st->invoice_id,
                        'order_date' => $st->invoice_date,
                        'status' => $st->transaction_status,
                        'total_amount' => $st->final_total_amount,
                        'subtotal' => $st->initial_total_amount,
                        'shipping_cost' => 0,
                        'discount_amount' => $st->initial_total_amount - $st->final_total_amount,
                        'payment_method' => 'Transfer Bank',
                        'payment_status' => $st->transaction_status === 'success' ? 'paid' : 'pending',
                        'payment_proof' => null,
                        'shipping_address' => optional($st->purchase_order->customer)->address ?? null,
                        'created_at' => $st->created_at,
                        'payment_confirmed_at' => $st->transaction_status === 'success' ? $st->delivery_confirmed_at : null,
                        'processing_at' => $st->created_at,
                        'shipped_at' => $st->delivery_confirmed_at,
                        'delivered_at' => $st->delivery_confirmed_at,
                        'customer' => (object) [
                            'name' => optional($st->purchase_order->customer)->name ?? 'Unknown',
                            'email' => optional(optional($st->purchase_order->customer)->user)->email ?? 'Unknown',
                            'phone' => optional($st->purchase_order->customer)->phone ?? null,
                        ],
                        'orderItems' => $st->sales_transaction_items->map(function ($item) {
                            return (object) [
                                'id' => $item->id,
                                'product' => (object) [
                                    'name' => $item->product->name ?? 'Unknown Product',
                                    'image' => $item->product->image ?? null,
                                    'brand' => (object) [
                                        'name' => optional($item->product->brand)->name ?? 'No Brand',
                                    ],
                                ],
                                'quantity' => $item->quantity_sold,
                                'price' => $item->msu_price,
                                'subtotal' => $item->quantity_sold * $item->msu_price,
                            ];
                        }),
                    ];
                }
            }

            if (!$orderData) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Order tidak ditemukan',
                    ],
                    404,
                );
            }

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
