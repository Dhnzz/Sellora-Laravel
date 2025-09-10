<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\SalesAgent;
use Illuminate\Http\Request;
use App\Models\SalesTransaction;
use App\Models\SupplierPurchase;

class AdminDashboardController
{
    public function salesChartData(Request $request)
    {
        $range = $request->input('range', 'weekly'); // weekly | monthly | yearly

        if ($range === 'weekly') {
            // 7 hari terakhir termasuk hari ini
            $dates = collect();
            for ($i = 6; $i >= 0; $i--) {
                $dates->push(now()->subDays($i)->toDateString());
            }
            $salesSeries = $dates->map(function ($date) {
                $sum = SalesTransaction::whereDate('invoice_date', $date)->sum('final_total_amount');
                return [
                    'x' => Carbon::parse($date)->format('d M'),
                    'y' => round($sum, 2),
                ];
            });
            return response()->json([
                'categories' => $salesSeries->pluck('x'),
                'series' => [
                    [
                        'name' => 'Penjualan',
                        'data' => $salesSeries->pluck('y'),
                    ]
                ],
            ]);
        } elseif ($range === 'monthly') {
            // Bulan berjalan per-hari
            $start = now()->startOfMonth();
            $end = now()->endOfMonth();
            $dates = collect();
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $dates->push($date->copy());
            }
            $salesSeries = $dates->map(function ($date) {
                $sum = SalesTransaction::whereDate('invoice_date', $date->toDateString())->sum('final_total_amount');
                return [
                    'x' => $date->format('d M'),
                    'y' => round($sum, 2),
                ];
            });
            return response()->json([
                'categories' => $salesSeries->pluck('x'),
                'series' => [
                    [
                        'name' => 'Penjualan',
                        'data' => $salesSeries->pluck('y'),
                    ]
                ],
            ]);
        } elseif ($range === 'yearly') {
            // Tahun berjalan per-bulan
            $months = collect(range(1, 12));
            $salesSeries = $months->map(function ($month) {
                $sum = SalesTransaction::whereYear('invoice_date', now()->year)->whereMonth('invoice_date', $month)->sum('final_total_amount');
                return [
                    'x' => Carbon::create(null, $month, 1)->format('M'),
                    'y' => round($sum, 2),
                ];
            });
            return response()->json([
                'categories' => $salesSeries->pluck('x'),
                'series' => [
                    [
                        'name' => 'Penjualan',
                        'data' => $salesSeries->pluck('y'),
                    ]
                ],
            ]);
        }

        return response()->json([
            'categories' => [],
            'series' => [['name' => 'Penjualan', 'data' => []], ['name' => 'Pembelanjaan', 'data' => []]],
        ]);
    }

    public function topSalesData(Request $request)
    {
        $range = $request->input('range', 'weekly');

        $query = SalesTransaction::query()->where('transaction_status','success');
        if ($range === 'weekly') {
            $query->whereBetween('invoice_date', [now()->subDays(6)->toDateString(), now()->toDateString()]);
        } elseif ($range === 'monthly') {
            $query->whereMonth('invoice_date', now()->month)->whereYear('invoice_date', now()->year);
        } elseif ($range === 'yearly') {
            $query->whereYear('invoice_date', now()->year);
        }

        $top = $query->selectRaw('sales_agent_id, COUNT(*) as total_tx')->groupBy('sales_agent_id')->orderByDesc('total_tx')->limit(5)->get();

        $labels = [];
        $dataCounts = [];
        foreach ($top as $row) {
            $agent = SalesAgent::find($row->sales_agent_id);
            $labels[] = $agent ? $agent->name : 'Sales #' . $row->sales_agent_id;
            $dataCounts[] = (int) $row->total_tx;
        }

        return response()->json([
            'labels' => $labels,
            'data' => $dataCounts,
        ]);
    }

    public function latest(Request $request)
    {
        $latestSales = SalesTransaction::with('sales_agent')
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'invoice_id', 'invoice_date', 'final_total_amount', 'sales_agent_id']);

        $sales = $latestSales->map(function ($s) {
            return [
                'invoice_id' => $s->invoice_id,
                'date' => Carbon::parse($s->invoice_date)->format('d M Y'),
                'amount' => number_format((float) $s->final_total_amount, 2, ',', '.'),
                'sales_agent' => optional($s->sales_agent)->name,
            ];
        });

        return response()->json([
            'sales' => $sales,
        ]);
    }
}
