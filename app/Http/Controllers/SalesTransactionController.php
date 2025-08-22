<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalesTransactionController
{
    public function index(Request $request)
    {
        $data = [
            'title' => 'Laporan Penjualan',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'report_sales_transaction',
            'breadcrumbs' => [
                [
                    'name' => 'Laporan',
                    'link' => '#',
                ],
                [
                    'name' => 'Penjualan',
                    'link' => route('owner.report.sales_transaction.index'),
                ],
            ],
        ];
        // Untuk tampilan awal, $products sudah di-paginate di atas
        return view('owner.report.sales_transaction.page.index', compact('data'));
    }
}
