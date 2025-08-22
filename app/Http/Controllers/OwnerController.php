<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalesTransaction;
use App\Models\SupplierPurchase;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class OwnerController
{
    public function dashboard()
    {
        $omset = SalesTransaction::sum('final_total_amount');
        $expense = SupplierPurchase::sum('total_amount');
        $income = $omset - $expense;

        $data = [
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'dashboard',
            'breadcrumbs' => [
                [
                    'name' => 'Dashboard',
                    'link' => route('owner.dashboard'),
                ],
            ],
            'omset' => $omset,
            'expense' => $expense,
            'income' => $income,
        ];
        return view('owner.dashboard', compact('data'));
    }

    public function filterData(Request $request)
    {
        $type = $request->input('type');
        $filter = $request->input('filter', 'all');

        $queryOmset = SalesTransaction::query();
        $queryExpense = SupplierPurchase::query();

        if ($type === 'omset') {
            // Current period
            if ($filter === 'day') {
                $queryOmset->whereDate('invoice_date', now());
            } elseif ($filter === 'month') {
                $queryOmset->whereMonth('invoice_date', now()->month)->whereYear('invoice_date', now()->year);
            } elseif ($filter === 'year') {
                $queryOmset->whereYear('invoice_date', now()->year);
            }

            $omset = $queryOmset->sum('final_total_amount');

            // Previous period
            $previousOmsetQuery = SalesTransaction::query();
            $periodLabel = null;
            if ($filter === 'day') {
                $previousOmsetQuery->whereDate('invoice_date', now()->subDay());
                $periodLabel = 'kemarin';
            } elseif ($filter === 'month') {
                $prev = now()->subMonth();
                $previousOmsetQuery->whereMonth('invoice_date', $prev->month)->whereYear('invoice_date', $prev->year);
                $periodLabel = 'bulan lalu';
            } elseif ($filter === 'year') {
                $prev = now()->subYear();
                $previousOmsetQuery->whereYear('invoice_date', $prev->year);
                $periodLabel = 'tahun lalu';
            }
            $previousOmset = isset($periodLabel) ? $previousOmsetQuery->sum('final_total_amount') : null;

            $direction = 'equal';
            $changePercent = null;
            if ($previousOmset !== null) {
                $diff = $omset - $previousOmset;
                $direction = $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'equal');
                $changePercent = $previousOmset > 0 ? round(($diff / $previousOmset) * 100, 2) : null;
            }
            return response()->json([
                'omset' => number_format($omset, 2, ',', '.'),
                'comparison' => [
                    'previous_formatted' => $previousOmset !== null ? number_format($previousOmset, 2, ',', '.') : null,
                    'change_percent' => $changePercent,
                    'direction' => $direction,
                    'period_label' => $periodLabel,
                ],
            ]);
        } elseif ($type === 'expense') {
            // Current period
            if ($filter === 'day') {
                $queryExpense->whereDate('purchase_date', now());
            } elseif ($filter === 'month') {
                $queryExpense->whereMonth('purchase_date', now()->month)->whereYear('purchase_date', now()->year);
            } elseif ($filter === 'year') {
                $queryExpense->whereYear('purchase_date', now()->year);
            }

            $expense = $queryExpense->sum('total_amount');
            // Previous period
            $previousExpenseQuery = SupplierPurchase::query();
            $periodLabel = null;
            if ($filter === 'day') {
                $previousExpenseQuery->whereDate('purchase_date', now()->subDay());
                $periodLabel = 'kemarin';
            } elseif ($filter === 'month') {
                $prev = now()->subMonth();
                $previousExpenseQuery->whereMonth('purchase_date', $prev->month)->whereYear('purchase_date', $prev->year);
                $periodLabel = 'bulan lalu';
            } elseif ($filter === 'year') {
                $prev = now()->subYear();
                $previousExpenseQuery->whereYear('purchase_date', $prev->year);
                $periodLabel = 'tahun lalu';
            }
            $previousExpense = isset($periodLabel) ? $previousExpenseQuery->sum('total_amount') : null;

            $direction = 'equal';
            $changePercent = null;
            if ($previousExpense !== null) {
                $diff = $expense - $previousExpense;
                $direction = $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'equal');
                $changePercent = $previousExpense > 0 ? round(($diff / $previousExpense) * 100, 2) : null;
            }
            return response()->json([
                'expense' => number_format($expense, 2, ',', '.'),
                'comparison' => [
                    'previous_formatted' => $previousExpense !== null ? number_format($previousExpense, 2, ',', '.') : null,
                    'change_percent' => $changePercent,
                    'direction' => $direction,
                    'period_label' => $periodLabel,
                ],
            ]);
        } elseif ($type === 'income') {
            // Current period
            if ($filter === 'day') {
                $queryOmset->whereDate('invoice_date', now());
                $queryExpense->whereDate('purchase_date', now());
            } elseif ($filter === 'month') {
                $queryOmset->whereMonth('invoice_date', now()->month)->whereYear('invoice_date', now()->year);
                $queryExpense->whereMonth('purchase_date', now()->month)->whereYear('purchase_date', now()->year);
            } elseif ($filter === 'year') {
                $queryOmset->whereYear('invoice_date', now()->year);
                $queryExpense->whereYear('purchase_date', now()->year);
            }

            $omset = $queryOmset->sum('final_total_amount');
            $expense = $queryExpense->sum('total_amount');
            $income = $omset - $expense;

            // Previous period
            $previousOmsetQuery = SalesTransaction::query();
            $previousExpenseQuery = SupplierPurchase::query();
            $periodLabel = null;
            if ($filter === 'day') {
                $previousOmsetQuery->whereDate('invoice_date', now()->subDay());
                $previousExpenseQuery->whereDate('purchase_date', now()->subDay());
                $periodLabel = 'kemarin';
            } elseif ($filter === 'month') {
                $prev = now()->subMonth();
                $previousOmsetQuery->whereMonth('invoice_date', $prev->month)->whereYear('invoice_date', $prev->year);
                $previousExpenseQuery->whereMonth('purchase_date', $prev->month)->whereYear('purchase_date', $prev->year);
                $periodLabel = 'bulan lalu';
            } elseif ($filter === 'year') {
                $prev = now()->subYear();
                $previousOmsetQuery->whereYear('invoice_date', $prev->year);
                $previousExpenseQuery->whereYear('purchase_date', $prev->year);
                $periodLabel = 'tahun lalu';
            }
            $previousIncome = null;
            if (isset($periodLabel)) {
                $previousOmset = $previousOmsetQuery->sum('final_total_amount');
                $previousExpense = $previousExpenseQuery->sum('total_amount');
                $previousIncome = $previousOmset - $previousExpense;
            }

            $direction = 'equal';
            $changePercent = null;
            if ($previousIncome !== null) {
                $diff = $income - $previousIncome;
                $direction = $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'equal');
                $changePercent = $previousIncome > 0 ? round(($diff / $previousIncome) * 100, 2) : null;
            }
            return response()->json([
                'omset' => number_format($omset, 2, ',', '.'),
                'expense' => number_format($expense, 2, ',', '.'),
                'income' => number_format($income, 2, ',', '.'),
                'comparison' => [
                    'previous_formatted' => $previousIncome !== null ? number_format($previousIncome, 2, ',', '.') : null,
                    'change_percent' => $changePercent,
                    'direction' => $direction,
                    'period_label' => $periodLabel,
                ],
            ]);
        }
    }

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
            $expenseSeries = $dates->map(function ($date) {
                $sum = SupplierPurchase::whereDate('purchase_date', $date)->sum('total_amount');
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
                    ],
                    [
                        'name' => 'Pembelanjaan',
                        'data' => $expenseSeries->pluck('y'),
                    ],
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
            $expenseSeries = $dates->map(function ($date) {
                $sum = SupplierPurchase::whereDate('purchase_date', $date->toDateString())->sum('total_amount');
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
                    ],
                    [
                        'name' => 'Pembelanjaan',
                        'data' => $expenseSeries->pluck('y'),
                    ],
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
            $expenseSeries = $months->map(function ($month) {
                $sum = SupplierPurchase::whereYear('purchase_date', now()->year)->whereMonth('purchase_date', $month)->sum('total_amount');
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
                    ],
                    [
                        'name' => 'Pembelanjaan',
                        'data' => $expenseSeries->pluck('y'),
                    ],
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

        $query = SalesTransaction::query();
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
            $agent = \App\Models\SalesAgent::find($row->sales_agent_id);
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

        $latestExpenses = SupplierPurchase::with('supplier')
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'invoice_number', 'purchase_date', 'total_amount', 'supplier_id']);

        $sales = $latestSales->map(function ($s) {
            return [
                'invoice_id' => $s->invoice_id,
                'date' => Carbon::parse($s->invoice_date)->format('d M Y'),
                'amount' => number_format((float) $s->final_total_amount, 2, ',', '.'),
                'sales_agent' => optional($s->sales_agent)->name,
            ];
        });

        $expenses = $latestExpenses->map(function ($e) {
            return [
                'invoice_number' => $e->invoice_number,
                'date' => Carbon::parse($e->purchase_date)->format('d M Y'),
                'amount' => number_format((float) $e->total_amount, 2, ',', '.'),
                'supplier' => optional($e->supplier)->name,
            ];
        });

        return response()->json([
            'sales' => $sales,
            'expenses' => $expenses,
        ]);
    }
}
