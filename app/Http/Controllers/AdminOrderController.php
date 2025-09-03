<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\SalesTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class AdminOrderController
{
    public function index()
    {
        $data = [
            'role' => Auth::user()->getRoleNames()->first(),
            'breadcrumbs' => [
                [
                    'name' => 'Manajemen Pesanan',
                    'link' => route('admin.orders.index'),
                ],
            ],
        ];

        return view('admin.orders.index', compact('data'));
    }

    public function getAll(Request $request)
    {
        if ($request->ajax()) {
            // Ambil data transaksi penjualan dengan relasi
            $query = SalesTransaction::with(['customer', 'sales_agent', 'sales_transaction_items'])
                ->select('sales_transactions.*')
                ->orderBy('created_at', 'desc');

            // Secara default order by created_at desc

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('customer_name', function ($row) {
                    return $row->customer ? $row->customer->name : 'N/A';
                })
                ->addColumn('customer_phone', function ($row) {
                    return $row->customer ? $row->customer->phone : 'N/A';
                })
                ->addColumn('status_label', function ($row) {
                    $status = $row->transaction_status;
                    $badge = '';

                    if ($status === 'pending') {
                        $badge = '<span class="badge bg-warning">Menunggu Konfirmasi</span>';
                    } elseif ($status === 'process') {
                        $badge = '<span class="badge bg-info">Sedang Diproses</span>';
                    } elseif ($status === 'success') {
                        $badge = '<span class="badge bg-success">Selesai</span>';
                    } elseif ($status === 'cancelled') {
                        $badge = '<span class="badge bg-danger">Dibatalkan</span>';
                    }

                    return $badge;
                })
                ->addColumn('actions', function ($row) {
                    $btn = '<div class="btn-group btn-group-sm">';
                    $btn .= '<button type="button" class="btn btn-outline-primary view-order" data-order-id="' . $row->id . '"><i class="ti ti-eye"></i></button>';

                    if ($row->transaction_status === 'pending') {
                        $btn .= '<button class="btn btn-outline-success confirm-order" data-order-id="' . $row->id . '"><i class="ti ti-check"></i></button>';
                    }

                    $btn .= '</div>';
                    return $btn;
                })
                ->editColumn('invoice_date', function ($row) {
                    return Carbon::parse($row->invoice_date)->format('d/m/Y');
                })
                ->editColumn('delivery_confirmed_at', function ($row) {
                    return $row->delivery_confirmed_at ? Carbon::parse($row->delivery_confirmed_at)->format('d/m/Y') : 'N/A';
                })
                ->editColumn('final_total_amount', function ($row) {
                    return 'Rp ' . number_format($row->final_total_amount, 0, ',', '.');
                })
                ->rawColumns(['status_label', 'actions'])
                ->make(true);
        }
    }

    /**
     * Menampilkan detail pesanan dalam format JSON untuk modal
     *
     * @param Request $request
     * @param int $order ID pesanan
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $order)
    {
        if ($request->ajax()) {
            // Ambil data transaksi dengan relasi yang dibutuhkan
            $transaction = SalesTransaction::with(['customer', 'sales_agent', 'admin', 'sales_transaction_items.product.product_unit', 'sales_transaction_items.product.product_brand'])->findOrFail($order);

            // Format data untuk ditampilkan di modal
            $formattedData = [
                'id' => $transaction->id,
                'invoice_id' => $transaction->invoice_id,
                'customer' => [
                    'name' => $transaction->customer ? $transaction->customer->name : 'N/A',
                    'phone' => $transaction->customer ? $transaction->customer->phone : 'N/A',
                    'address' => $transaction->customer ? $transaction->customer->address : 'N/A',
                ],
                'sales_agent' => [
                    'name' => $transaction->sales_agent ? $transaction->sales_agent->name : 'N/A'
                ],
                'admin' => [
                    'name' => $transaction->admin ? $transaction->admin->name : 'N/A'
                ],
                'order_date' => Carbon::parse($transaction->order_date)->format('d/m/Y'),
                'invoice_date' => Carbon::parse($transaction->invoice_date)->format('d/m/Y'),
                'delivery_confirmed_at' => $transaction->delivery_confirmed_at ? Carbon::parse($transaction->delivery_confirmed_at)->format('d/m/Y') : 'N/A',
                'initial_total_amount' => 'Rp ' . number_format($transaction->initial_total_amount, 0, ',', '.'),
                'final_total_amount' => 'Rp ' . number_format($transaction->final_total_amount, 0, ',', '.'),
                'note' => $transaction->note,
                'status' => $transaction->transaction_status,
                'status_label' => $this->getStatusLabel($transaction->transaction_status),
                'cancel_note' => $transaction->cancel_note,
                'items' => [],
            ];

            // Format data item pesanan
            foreach ($transaction->sales_transaction_items as $item) {
                $formattedData['items'][] = [
                    'product_name' => $item->product->name,
                    'product_brand' => $item->product->product_brand->name,
                    'quantity' => $item->quantity_sold,
                    'unit' => $item->product->product_unit->name,
                    'discount' => $item->product->discount,
                    'price' => 'Rp ' . number_format($item->msu_price, 0, ',', '.'),
                    'subtotal' => 'Rp ' . number_format($item->product->discount > 0.0 ? ($item->msu_price - $item->msu_price * $item->product->discount) * $item->quantity_sold : $item->quantity_sold * $item->msu_price, 0, ',', '.'),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $formattedData,
            ]);
        }

        // Jika bukan request AJAX, redirect ke halaman index
        // return redirect()->route('admin.orders.index');
        return response()->json(['error' => 'Silahkan load menggunakan ajax']);
    }

    /**
     * Mendapatkan label status dalam format HTML
     *
     * @param string $status
     * @return string
     */
    private function getStatusLabel($status)
    {
        if ($status === 'pending') {
            return '<span class="badge bg-warning">Menunggu Konfirmasi</span>';
        } elseif ($status === 'process') {
            return '<span class="badge bg-info">Sedang Diproses</span>';
        } elseif ($status === 'success') {
            return '<span class="badge bg-success">Selesai</span>';
        } elseif ($status === 'cancelled') {
            return '<span class="badge bg-danger">Dibatalkan</span>';
        }

        return '<span class="badge bg-secondary">Unknown</span>';
    }
}
