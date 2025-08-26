<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminOrderController
{
    public function index()
    {
        $orders = PurchaseOrder::with(['customer', 'purchase_order_items.product'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.orders.index', compact('orders'));
    }

    public function show(PurchaseOrder $order)
    {
        $order->load(['customer', 'purchase_order_items.product.product_brand']);

        return view('admin.orders.show', compact('order'));
    }

    public function confirm(PurchaseOrder $order)
    {
        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Order sudah dikonfirmasi atau dibatalkan',
            ]);
        }

        DB::transaction(function () use ($order) {
            // Update status PO
            $order->update(['status' => 'confirmed']);

            // Hitung total
            $total = 0;
            foreach ($order->purchase_order_items as $item) {
                $total += $item->quantity_ordered * $item->unit_price;
            }

            // Buat Sales Transaction
            $salesTransaction = SalesTransaction::create([
                'purchase_order_id' => $order->id,
                'admin_id' => Auth::id(),
                'sales_agent_id' => null, // Bisa diisi jika ada sales agent
                'invoice_id' => 'INV-' . date('Ymd') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
                'invoice_date' => now(),
                'discount_percent' => 0,
                'initial_total_amount' => $total,
                'final_total_amount' => $total,
                'note' => 'Order dari customer: ' . $order->customer->name,
                'transaction_status' => 'pending', // pending, completed, cancelled
            ]);

            // Buat Sales Transaction Items
            foreach ($order->purchase_order_items as $item) {
                SalesTransactionItem::create([
                    'sales_transaction_id' => $salesTransaction->id,
                    'product_id' => $item->product_id,
                    'quantity_ordered' => $item->quantity_ordered,
                    'quantity_sold' => $item->quantity_ordered,
                    'msu_price' => $item->unit_price,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Order berhasil dikonfirmasi dan Sales Transaction dibuat',
        ]);
    }

    public function cancel(PurchaseOrder $order)
    {
        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak dapat dibatalkan',
            ]);
        }

        $order->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Order berhasil dibatalkan',
        ]);
    }
}
