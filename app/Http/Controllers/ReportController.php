<?php

namespace App\Http\Controllers;

use App\Models\SalesAgent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Exports\ReportExport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController
{
    public function index(Request $request)
    {
        $data = [
            'title' => 'Laporan',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'report_index',
            'breadcrumbs' => [
                [
                    'name' => 'Laporan',
                    'link' => route('owner.report.index'),
                ],
            ],
        ];
        return view('owner.report.index', compact('data'));
    }

    // Ambil data untuk cards + tabel + chart
    public function data(Request $r)
    {
        [$from, $to] = $this->dateRange($r);

        // Base query: sales_transactions
        $base = DB::table('sales_transactions as st')->when($from, fn($q) => $q->whereDate('st.invoice_date', '>=', $from))->when($to, fn($q) => $q->whereDate('st.invoice_date', '<=', $to))->when($r->filled('sales_id'), fn($q) => $q->where('st.sales_agent_id', $r->input('sales_id')))->when($r->filled('status'), fn($q) => $q->where('st.transaction_status', $r->input('status')));

        // ====== Cards ======
        // Gross = sum(initial_total_amount)
        // Discount = sum(initial - final). Kalau lo punya kolom diskon terpisah, tinggal ganti.
        $cardsRow = (clone $base)
            ->selectRaw(
                '
                COALESCE(SUM(st.initial_total_amount),0) as gross,
                COALESCE(SUM(st.initial_total_amount - st.final_total_amount),0) as discount,
                0 as tax,                 -- TODO: ganti kalau ada kolom pajak
                0 as other_expense,       -- TODO: ganti kalau ada biaya lain
                0 as return_total,        -- sementara 0, di bawah kita hitung retur beneran
                COUNT(*) as trx_count,
                COALESCE(SUM(st.final_total_amount),0) as net_before_return
            ',
            )
            ->first();

        // Hitung retur (opsional) lewat delivery_returns (asumsi punya total_amount & relasi via sales_transaction_id)
        $returnTotal = DB::table('delivery_returns as dr')
            ->join('sales_transactions as st2', 'st2.id', '=', 'dr.sales_transaction_id')
            ->when($from, fn($q) => $q->whereDate('st2.invoice_date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('st2.invoice_date', '<=', $to))
            ->when($r->filled('sales_id'), fn($q) => $q->where('st2.sales_agent_id', $r->input('sales_id')))
            ->when($r->filled('status'), fn($q) => $q->where('st2.transaction_status', $r->input('status')))
            ->when($r->filled('customer_id'), function ($q) use ($r) {
                $q->join('purchase_orders as po2', 'po2.id', '=', 'st2.purchase_order_id')->where('po2.customer_id', $r->input('customer_id'));
            })
            ->sum('dr.total_amount');

        $netSales = (float) $cardsRow->net_before_return - (float) $returnTotal;

        // ====== Trend per tanggal (pakai final_total_amount) ======
        $trend = (clone $base)->selectRaw('DATE(st.invoice_date) as d, SUM(st.final_total_amount) as total')->groupBy(DB::raw('DATE(st.invoice_date)'))->orderBy('d')->get();

        // ====== Breakdown metode pembayaran (kosong dulu, kolom belum ada) ======
        // $methods = collect(); // kalau nanti ada, tinggal groupBy kolom payment_method

        // ====== Top Produk ======
        $topProductLimit = max(1, (int) $r->get('top_product_limit', 10));
        $topProducts = DB::table('sales_transaction_items as sti')
            ->join('sales_transactions as st', 'st.id', '=', 'sti.sales_transaction_id')
            ->join('products as p', 'p.id', '=', 'sti.product_id')
            ->when($from, fn($q) => $q->whereDate('st.invoice_date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('st.invoice_date', '<=', $to))
            ->when($r->filled('sales_id'), fn($q) => $q->where('st.sales_agent_id', $r->input('sales_id')))
            ->when($r->filled('status'), fn($q) => $q->where('st.transaction_status', $r->input('status')))
            ->when($r->filled('customer_id'), function ($q) use ($r) {
                $q->join('purchase_orders as po3', 'po3.id', '=', 'st.purchase_order_id')->where('po3.customer_id', $r->input('customer_id'));
            })
            ->selectRaw(
                '
                    p.id,
                    p.name,
                    SUM(sti.quantity_sold) as qty,
                    SUM(sti.quantity_sold * sti.msu_price) as omzet
                ',
            )
            ->groupBy('p.id', 'p.name')
            ->orderByDesc('omzet')
            ->limit($topProductLimit)
            ->get();

        // ====== Top Customers ======
        $topCustomerLimit = max(1, (int) $r->get('top_customer_limit', 10));
        $topCustomers = DB::table('sales_transactions as st')
            ->join('purchase_orders as po', 'po.id', '=', 'st.purchase_order_id') // asumsi
            ->leftJoin('customers as c', 'c.id', '=', 'po.customer_id') // asumsi
            ->when($from, fn($q) => $q->whereDate('st.invoice_date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('st.invoice_date', '<=', $to))
            ->when($r->filled('sales_id'), fn($q) => $q->where('st.sales_agent_id', $r->input('sales_id')))
            ->when($r->filled('status'), fn($q) => $q->where('st.transaction_status', $r->input('status')))
            ->selectRaw('po.customer_id, COALESCE(c.name,"-") as customer, COUNT(*) as trx_count, SUM(st.final_total_amount) as omzet')
            ->groupBy('po.customer_id', 'c.name')
            ->orderByDesc('omzet')
            ->limit($topCustomerLimit)
            ->get();

        // ====== Table Invoices ======
        $invoices = (clone $base)
            ->leftJoin('purchase_orders as po', 'po.id', '=', 'st.purchase_order_id')
            ->leftJoin('customers as c', 'c.id', '=', 'po.customer_id')
            ->leftJoin('sales_agents as sa', 'sa.id', '=', 'st.sales_agent_id') // asumsi
            ->selectRaw(
                '
                st.id,
                st.invoice_id as invoice_number,
                st.invoice_date as date,
                COALESCE(c.name,"-") as customer,
                COALESCE(sa.name,"-") as sales,
                st.initial_total_amount as subtotal,
                (st.initial_total_amount - st.final_total_amount) as discount,
                0 as return_total,
                st.final_total_amount as total,
                st.transaction_status as status
            ',
            )
            ->orderByDesc('st.invoice_date')
            ->limit(100)
            ->get();

        return response()->json([
            'sales' => SalesAgent::all(),
            'cards' => [
                'gross' => (float) $cardsRow->gross,
                'discount' => (float) $cardsRow->discount,
                'return_total' => (float) $returnTotal,
                'net_sales' => (float) $netSales,
                'trx_count' => (int) $cardsRow->trx_count,
                'aov' => (float) round($cardsRow->trx_count ? $netSales / $cardsRow->trx_count : 0, 2),
            ],
            'trend' => $trend,
            // 'methods' => $methods, // kosong dulu
            'top_products' => $topProducts,
            'top_customers' => $topCustomers,
            'invoices' => $invoices,
        ]);
    }

    public function invoicesData(Request $r)
    {
        // ====== Param standar DataTables ======
        $draw = (int) $r->input('draw', 1);
        $start = (int) $r->input('start', 0); // offset
        $length = (int) $r->input('length', 10); // page size
        $search = trim($r->input('search.value', ''));

        // Mapping kolom utk ordering (index kolom -> nama kolom DB)
        $orderColIdx = (int) $r->input('order.0.column', 0);
        $orderDir = $r->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $cols = [
            0 => 'st.invoice_date',
            1 => 'st.id',
            2 => 'c.name',
            3 => 'sa.name',
            4 => 'st.initial_total_amount',
            5 => 'discount_amount_alias', // alias, kita hitung manual
            7 => 'return_amount_alias', // alias
            8 => 'st.final_total_amount',
            9 => 'st.transaction_status',
        ];
        $orderBy = $cols[$orderColIdx] ?? 'st.invoice_date';

        // ====== Filter tanggal dll (sama seperti base query di data()) ======
        [$from, $to] = $this->dateRange($r);

        $base = DB::table('sales_transactions as st')
            ->leftJoin('purchase_orders as po', 'po.id', '=', 'st.purchase_order_id')
            ->leftJoin('customers as c', 'c.id', '=', 'po.customer_id')
            ->leftJoin('sales_agents as sa', 'sa.id', '=', 'st.sales_agent_id')
            ->when($from, fn($q) => $q->whereDate('st.invoice_date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('st.invoice_date', '<=', $to))
            ->when($r->filled('sales_id'), fn($q) => $q->where('st.sales_agent_id', $r->input('sales_id')))
            ->when($r->filled('status'), fn($q) => $q->where('st.transaction_status', $r->input('status')))
            ->when($r->filled('customer_id'), function ($q) use ($r) {
                // pakai whereExists biar aman dari duplikasi
                $q->whereExists(function ($sub) use ($r) {
                    $sub->from('purchase_orders as po2')->whereColumn('po2.id', 'st.purchase_order_id')->where('po2.customer_id', $r->input('customer_id'));
                });
            });

        // ====== Total baris tanpa filter pencarian (recordsTotal) ======
        $recordsTotal = (clone $base)->count('st.id');

        // ====== Pencarian global (search box DataTables) ======
        if ($search !== '') {
            $base->where(function ($qq) use ($search) {
                $qq->where('st.id', 'like', "%{$search}%")
                    ->orWhere('c.name', 'like', "%{$search}%")
                    ->orWhere('sa.name', 'like', "%{$search}%")
                    ->orWhere('st.transaction_status', 'like', "%{$search}%");
            });
        }

        // ====== Total setelah filter pencarian (recordsFiltered) ======
        $recordsFiltered = (clone $base)->count('st.id');

        // ====== Ambil data page ini ======
        $rows = (clone $base)
            ->selectRaw(
                '
            st.id,
            st.invoice_id as invoice_number,
            st.invoice_date as date,
            COALESCE(c.name,"-")  as customer,
            COALESCE(sa.name,"-") as sales,
            st.initial_total_amount as subtotal,
            (st.initial_total_amount - st.final_total_amount) as discount_amount_alias,
            0 as tax_amount,           -- kalau ada kolom pajak, ganti di sini
            0 as return_amount_alias,  -- kalau mau, isi dari tabel retur per invoice
            st.final_total_amount as total,
            st.transaction_status as status
        ',
            )
            ->when($orderBy === 'discount_amount_alias', fn($q) => $q->orderByRaw('(st.initial_total_amount - st.final_total_amount) ' . $orderDir))
            ->when($orderBy === 'return_amount_alias', fn($q) => $q->orderByRaw('0 ' . $orderDir)) // placeholder, ganti kalau sudah ada nilai retur per invoice
            ->when(!in_array($orderBy, ['discount_amount_alias', 'return_amount_alias']), fn($q) => $q->orderBy($orderBy, $orderDir))
            ->skip($start)
            ->take($length)
            ->get();

        // ====== Format data untuk DataTables ======
        // Biar ringan, formatting currency bisa di client. Kalau mau, format di sini juga boleh.
        $data = $rows->map(function ($r) {
            return [
                'date' => $r->date,
                'id' => $r->id,
                'invoice_number' => $r->invoice_number,
                'customer' => $r->customer,
                'sales' => $r->sales,
                'subtotal' => (float) $r->subtotal,
                'discount' => (float) $r->discount_amount_alias,
                'retur' => (float) $r->return_amount_alias,
                'total' => (float) $r->total,
                'status' => $r->status,
                'action' => '', // tombol detail/edit kalau perlu
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function invoiceShow($id)
    {
        // Header transaksi
        $h = DB::table('sales_transactions as st')
            ->leftJoin('purchase_orders as po', 'po.id', '=', 'st.purchase_order_id')
            ->leftJoin('customers as c', 'c.id', '=', 'po.customer_id')
            ->leftJoin('sales_agents as sa', 'sa.id', '=', 'st.sales_agent_id')
            ->where('st.id', $id)
            ->selectRaw(
                '
            st.id,
            st.invoice_id as invoice_number,
            st.invoice_date,
            COALESCE(c.name,"-")  as customer,
            COALESCE(sa.name,"-") as sales,
            st.initial_total_amount as subtotal,
            (st.initial_total_amount - st.final_total_amount) as discount,
            st.final_total_amount as total,
            st.transaction_status as status
        ',
            )
            ->first();

        if (!$h) {
            return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
        }

        // Total retur (pakai header delivery_returns.total_amount kalau ada)
        $returnTotal = DB::table('delivery_returns as dr')->where('dr.sales_transaction_id', $id)->sum('dr.total_amount');

        // Items
        $items = DB::table('sales_transaction_items as sti')
            ->join('products as p', 'p.id', '=', 'sti.product_id')
            ->where('sti.sales_transaction_id', $id)
            ->selectRaw(
                '
            p.name,
            sti.quantity_sold as qty,
            sti.msu_price as price,
            (sti.quantity_sold * sti.msu_price) as line_total
        ',
            )
            ->get();

        // Recompute grand total (kalau mau tampilkan retur)
        $header = [
            'id' => $h->id,
            'date' => $h->invoice_date,
            'customer' => $h->customer,
            'sales' => $h->sales,
            'subtotal' => (float) $h->subtotal,
            'discount' => (float) $h->discount,
            'return' => (float) $returnTotal,
            'total' => (float) $h->total, // total setelah diskon (sebelum retur)
            'status' => $h->status,
            'net_after_return' => (float) $h->total - (float) $returnTotal, // opsional
        ];

        return response()->json([
            'header' => $header,
            'items' => $items,
        ]);
    }

    public function exportInvoicePdf($id)
    {
        // header transaksi
        $h = DB::table('sales_transactions as st')
            ->leftJoin('purchase_orders as po', 'po.id', '=', 'st.purchase_order_id')
            ->leftJoin('customers as c', 'c.id', '=', 'po.customer_id')
            ->leftJoin('sales_agents as sa', 'sa.id', '=', 'st.sales_agent_id')
            ->where('st.id', $id)
            ->selectRaw(
                '
            st.id,
            st.invoice_id as invoice_number,
            st.invoice_date,
            COALESCE(c.name,"-")  as customer,
            COALESCE(sa.name,"-") as sales,
            st.initial_total_amount as subtotal,
            (st.initial_total_amount - st.final_total_amount) as discount,
            st.final_total_amount as total,
            st.transaction_status as status
        ',
            )
            ->first();

        if (!$h) {
            abort(404, 'Transaksi tidak ditemukan');
        }

        // total retur
        $returnTotal = DB::table('delivery_returns as dr')->where('dr.sales_transaction_id', $id)->sum('dr.total_amount');

        // items
        $items = DB::table('sales_transaction_items as sti')
            ->join('products as p', 'p.id', '=', 'sti.product_id')
            ->where('sti.sales_transaction_id', $id)
            ->selectRaw(
                '
            p.name,
            sti.quantity_sold as qty,
            sti.msu_price as price,
            (sti.quantity_sold * sti.msu_price) as line_total
        ',
            )
            ->get();

        $payload = [
            'header' => [
                'id' => $h->id,
                'invoice_number' => $h->invoice_number,
                'date' => $h->invoice_date,
                'customer' => $h->customer,
                'sales' => $h->sales,
                'subtotal' => (float) $h->subtotal,
                'discount' => (float) $h->discount,
                'return' => (float) $returnTotal,
                'total' => (float) $h->total,
                'status' => $h->status,
                'grand' => (float) $h->total - (float) $returnTotal,
            ],
            'items' => $items,
        ];

        $orientation = request('orientation') === 'landscape' ? 'landscape' : 'portrait'; // optional
        $pdf = Pdf::loadView('owner.report.invoice-pdf', $payload)->setPaper('A4', $orientation);

        // ?stream=1 untuk preview di tab
        return request()->boolean('stream') ? $pdf->stream("invoice-{$h->invoice_number}.pdf") : $pdf->download("invoice-{$h->invoice_number}.pdf");
    }

    public function exportXlsx(Request $r)
    {
        [$from, $to] = $this->dateRange($r);

        // Format tanggal untuk nama file
        $fromStr = $from ? Carbon::parse($from)->format('Ymd') : 'all';
        $toStr = $to ? Carbon::parse($to)->format('Ymd') : 'all';

        if ($fromStr === 'all' && $toStr === 'all') {
            $filename = 'salesReport_All.xlsx';
        } else {
            $filename = "salesReport_{$fromStr}_{$toStr}.xlsx";
        }

        return Excel::download(new ReportExport($r->all(), $from, $to), $filename);
    }

    public function exportCsv(Request $r)
    {
        [$from, $to] = $this->dateRange($r);

        // Format tanggal untuk nama file
        $fromStr = $from ? Carbon::parse($from)->format('Ymd') : 'all';
        $toStr = $to ? Carbon::parse($to)->format('Ymd') : 'all';

        if ($fromStr === 'all' && $toStr === 'all') {
            $filename = 'salesReport_All.csv';
        } else {
            $filename = "salesReport_{$fromStr}_{$toStr}.csv";
        }
        return Excel::download(new ReportExport($r->all(), $from, $to), $filename, ExcelFormat::CSV);
    }

    public function exportPdf(Request $r)
    {
        // ambil payload dari method data() biar 1 sumber kebenaran (ikut filter: from/to/status/limits)
        $reqClone = Request::create('', 'GET', $r->all());
        $payload = $this->data($reqClone)->getData(true); // array siap dipakai di Blade

        // orientation opsional via query ?orientation=landscape
        $orientation = $r->get('orientation') === 'landscape' ? 'landscape' : 'portrait';

        // Agar variabel di view sama seperti saat Pdf::loadView, gunakan spread operator
        // return view('owner.report.pdf', $payload + ['orientation' => $orientation]);

        // // render PDF dari Blade yang sudah kamu punya
        $pdf = Pdf::loadView('owner.report.pdf', $payload)->setPaper('A4', $orientation);

        // // ?stream=1 untuk buka di tab (preview), default download
        return $r->boolean('stream') ? $pdf->stream('sales-report.pdf') : $pdf->download('sales-report.pdf');
    }

    private function dateRange(Request $r): array
    {
        $from = $r->filled('from') ? Carbon::parse($r->from)->startOfDay() : null;
        $to = $r->filled('to') ? Carbon::parse($r->to)->endOfDay() : null;
        return [$from, $to];
    }
}
