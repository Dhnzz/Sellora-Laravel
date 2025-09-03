<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\SalesTransaction;
use App\Models\SalesAgent;
use App\Models\Admin as AdminModel;
use Illuminate\Support\Facades\DB;
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
            $query = SalesTransaction::with(['customer', 'sales_agent', 'sales_transaction_items'])->select('sales_transactions.*');

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
                        $badge = '<span class="badge bg-warning" style="width: 100px">Pending</span>';
                    } elseif ($status === 'process') {
                        $badge = '<span class="badge bg-info" style="width: 100px">Diproses</span>';
                    } elseif ($status === 'success') {
                        $badge = '<span class="badge bg-success" style="width: 100px">Selesai</span>';
                    } elseif ($status === 'cancelled') {
                        $badge = '<span class="badge bg-danger" style="width: 100px">Dibatalkan</span>';
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
                // Tambahkan fungsi sort dan search
                ->filter(function ($query) use ($request) {
                    // Search
                    if ($search = $request->input('search.value')) {
                        $query->where(function ($q) use ($search) {
                            $q->where('invoice_id', 'like', "%{$search}%")
                                ->orWhereHas('customer', function ($q2) use ($search) {
                                    $q2->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%");
                                })
                                ->orWhereHas('sales_agent', function ($q3) use ($search) {
                                    $q3->where('name', 'like', "%{$search}%");
                                })
                                ->orWhere('final_total_amount', 'like', "%{$search}%");
                        });
                    }
                })
                ->order(function ($query) use ($request) {
                    // Jika tidak ada parameter order dari DataTables, urutkan default by created_at desc
                    if (!$request->has('order') || empty($request->input('order'))) {
                        $query->orderBy('created_at', 'desc');
                        return;
                    }

                    // Sortir berdasarkan kolom yang diminta DataTables
                    $order = $request->input('order')[0];
                    $columnIndex = $order['column'];
                    $columnName = $request->input('columns')[$columnIndex]['data'];
                    $sortDirection = $order['dir'];

                    // Kolom yang bisa di-sort
                    $sortableColumns = ['id', 'invoice_id', 'customer_name', 'invoice_date', 'final_total_amount', 'status_label'];

                    if (in_array($columnName, $sortableColumns)) {
                        if ($columnName === 'customer_name') {
                            // Pastikan join ke tabel customers jika belum
                            if (
                                !collect($query->getQuery()->joins)
                                    ->pluck('table')
                                    ->contains('customers')
                            ) {
                                $query->leftJoin('customers', 'sales_transactions.customer_id', '=', 'customers.id');
                            }
                            $query->orderBy('customers.name', $sortDirection)->select('sales_transactions.*');
                        } elseif ($columnName === 'status_label') {
                            $query->orderBy('transaction_status', $sortDirection)->select('sales_transactions.*');
                        } else {
                            $query->orderBy($columnName, $sortDirection);
                        }
                    } elseif ($columnName === 'DT_RowIndex') {
                        // Abaikan sorting untuk kolom nomor urut jika tidak diperlukan
                        // $query->orderBy('created_at', 'desc');
                    }
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
     * Daftar Sales Agent beserta jumlah order aktif (pending/process)
     */
    public function salesAgents(Request $request)
    {
        $agents = SalesAgent::query()
            ->leftJoin('sales_transactions as st', function ($join) {
                $join->on('st.sales_agent_id', '=', 'sales_agents.id')->whereIn('st.transaction_status', ['pending', 'process']);
            })
            ->groupBy('sales_agents.id', 'sales_agents.name')
            ->select('sales_agents.id', 'sales_agents.name', DB::raw('COUNT(st.id) as active_orders'))
            ->orderBy('sales_agents.name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $agents,
        ]);
    }

    /**
     * Konfirmasi pesanan dan tetapkan Sales Agent
     */
    public function confirm(Request $request, $orderId)
    {
        $request->validate([
            'sales_agent_id' => 'required', // 'auto' atau numeric id
        ]);

        $transaction = SalesTransaction::findOrFail($orderId);

        // Tentukan admin_id dari user yang login
        $adminId = AdminModel::where('user_id', Auth::id())->value('id');

        // Tentukan sales agent: manual atau auto-assign
        $salesAgentIdInput = $request->input('sales_agent_id');
        if ($salesAgentIdInput === 'auto') {
            $chosen = SalesAgent::query()
                ->leftJoin('sales_transactions as st', function ($join) {
                    $join->on('st.sales_agent_id', '=', 'sales_agents.id')->whereIn('st.transaction_status', ['pending', 'process']);
                })
                ->groupBy('sales_agents.id')
                ->select('sales_agents.id', DB::raw('COUNT(st.id) as active_orders'))
                ->orderBy('active_orders')
                ->orderBy('sales_agents.id')
                ->first();

            if (!$chosen) {
                return response()->json(['success' => false, 'message' => 'Tidak ada Sales Agent yang tersedia'], 422);
            }
            $salesAgentId = $chosen->id;
        } else {
            $salesAgentId = (int) $salesAgentIdInput;
            if (!SalesAgent::where('id', $salesAgentId)->exists()) {
                return response()->json(['success' => false, 'message' => 'Sales Agent tidak ditemukan'], 422);
            }
        }

        $transaction->update([
            'transaction_status' => 'process',
            'sales_agent_id' => $salesAgentId,
            'admin_id' => $adminId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil dikonfirmasi dan Sales Agent ditetapkan.',
        ]);
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
            $transaction = SalesTransaction::with(['customer', 'sales_agent', 'sales_transaction_items.product.product_unit', 'sales_transaction_items.product.product_brand'])->findOrFail($order);

            // Format data untuk ditampilkan di modal
            $formattedData = [
                'id' => $transaction->id,
                'invoice_id' => $transaction->invoice_id,
                'customer' => [
                    'name' => $transaction->customer ? $transaction->customer->name : 'N/A',
                    'phone' => $transaction->customer ? $transaction->customer->phone : 'N/A',
                    'address' => $transaction->customer ? $transaction->customer->address : 'N/A',
                ],
                'sales_agent' => $transaction->sales_agent ? $transaction->sales_agent->name : 'N/A',
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
        return response()->json(['error' => 'Silahkan akses menggunakan ajax']);
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
            return '<span class="badge bg-warning">Pending</span>';
        } elseif ($status === 'process') {
            return '<span class="badge bg-info">Diproses</span>';
        } elseif ($status === 'success') {
            return '<span class="badge bg-success">Selesai</span>';
        } elseif ($status === 'cancelled') {
            return '<span class="badge bg-danger">Dibatalkan</span>';
        }

        return '<span class="badge bg-secondary">Unknown</span>';
    }
}
