<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\SalesTransaction;
use Exception;
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

            // Query Sales Transactions langsung
            $stQuery = SalesTransaction::where('customer_id', $customer->id)->with(['customer.user', 'sales_transaction_items.product.product_brand'])->orderBy('created_at', 'desc');

            if ($status) {
                $stQuery->where('transaction_status', $status);
            }
            if ($periodDays) {
                $stQuery->where('invoice_date', '>=', Carbon::now()->subDays($periodDays));
            }

            // Ambil data mentah (tanpa paginate), lalu normalisasi struktur
            $stList = $stQuery->get()->map(function ($order) {
                return [
                    'id' => $order->id,
                    'invoice_number' => $order->invoice_id,
                    'invoice_date' => $order->invoice_date,
                    'status' => $order->transaction_status,
                    'total_amount' => $order->final_total_amount,
                    'subtotal' => $order->initial_total_amount,
                    'shipping_address' => $order->customer->address ?? null,
                    'created_at' => $order->created_at,
                    'payment_confirmed_at' => $order->transaction_status === 'success' ? $order->delivery_confirmed_at : null,
                    'processing_at' => $order->created_at,
                    'shipped_at' => $order->delivery_confirmed_at,
                    'delivered_at' => $order->delivery_confirmed_at,
                    'customer' => [
                        'name' => $order->customer->name ?? 'Unknown',
                        'email' => optional($order->customer->user)->email ?? 'Unknown',
                        'phone' => $order->customer->phone ?? null,
                    ],
                    'orderItems' => $order->sales_transaction_items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product' => [
                                'name' => $item->product->name ?? 'Unknown Product',
                                'image' => $item->product->image ?? null,
                                'brand' => [
                                    'name' => optional($item->product->product_brand)->name ?? 'No Brand',
                                ],
                            ],
                            'quantity' => $item->quantity_sold,
                            'price' => $item->msu_price,
                            'subtotal' => $item->quantity_sold * $item->msu_price,
                        ];
                    }),
                ];
            });

            // Gunakan hanya SalesTransaction
            $all = $stList;

            // Sorting gabungan
            $sorted = $all
                ->sort(function ($a, $b) use ($sort) {
                    switch ($sort) {
                        case 'oldest':
                            return strtotime($a['invoice_date']) <=> strtotime($b['invoice_date']);
                        case 'highest':
                            return $b['total_amount'] <=> $a['total_amount'];
                        case 'lowest':
                            return $a['total_amount'] <=> $b['total_amount'];
                        case 'latest':
                        default:
                            return strtotime($b['invoice_date']) <=> strtotime($a['invoice_date']);
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

            // Cari SalesTransaction berdasarkan ID
            $st = SalesTransaction::where('id', $id)
                ->where('customer_id', $customer->id)
                ->with(['customer.user', 'sales_transaction_items.product.product_brand'])
                ->first();

            if (!$st) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Order tidak ditemukan',
                    ],
                    404,
                );
            }

            $orderData = (object) [
                'id' => $st->id,
                'order_number' => $st->invoice_id,
                'order_date' => $st->order_date,
                'status' => $st->transaction_status,
                'total_amount' => $st->final_total_amount,
                'subtotal' => $st->initial_total_amount,
                'shipping_cost' => 0,
                'discount_amount' => $st->initial_total_amount - $st->final_total_amount,
                'payment_method' => 'Transfer Bank',
                'payment_status' => $st->transaction_status === 'success' ? 'paid' : 'pending',
                'payment_proof' => null,
                'shipping_address' => $st->customer->address ?? null,
                'created_at' => $st->created_at,
                'payment_confirmed_at' => $st->transaction_status === 'success' ? $st->delivery_confirmed_at : null,
                'processing_at' => $st->created_at,
                'shipped_at' => $st->delivery_confirmed_at,
                'delivered_at' => $st->delivery_confirmed_at,
                'customer' => (object) [
                    'name' => $st->customer->name ?? 'Unknown',
                    'email' => optional($st->customer->user)->email ?? 'Unknown',
                    'phone' => $st->customer->phone ?? null,
                ],
                'orderItems' => $st->sales_transaction_items->map(function ($item) {
                    $netPrice = $item->product->discount > 0.0 ? $item->product->selling_price - $item->product->selling_price * $item->product->discount : $item->product->selling_price;
                    return (object) [
                        'id' => $item->id,
                        'product' => (object) [
                            'name' => $item->product->name ?? 'Unknown Product',
                            'image' => $item->product->image ?? null,
                            'discount' => $item->product->discount,
                            'brand' => (object) [
                                'name' => optional($item->product->product_brand)->name ?? 'No Brand',
                            ],
                        ],
                        'quantity' => $item->quantity_sold,
                        'price' => $item->product->selling_price,
                        'net_price' => $netPrice,
                        'subtotal' => $item->product->discount > 0.0 ? $item->quantity_sold * $netPrice : $item->quantity_sold * $item->product->selling_price,
                    ];
                }),
            ];

            $html = view('customer.order.detail', compact('orderData'))->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'orderId' => $orderData->id,
                'orderStatus' => $orderData->status
            ]);
        } catch (Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function cancelOrder(Request $request, $id)
    {
        $st = SalesTransaction::findOrFail($id);
        try {
            $st->update([
                'transaction_status' => 'cancelled',
                'cancel_note' => $request->cancelNote
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Berhasil membatalkan order',
            ]);
        } catch (Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Gagal membatalkan order : ' . $e->getMessage(),
                ],
                500,
            );
        }
    }
}
