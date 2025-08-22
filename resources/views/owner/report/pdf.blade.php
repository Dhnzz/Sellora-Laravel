@php
    // Payload datang dari ReportController::exportPdf -> $this->data(...)->getData(true)
    // Struktur: ['cards'=>..., 'trend'=>..., 'top_products'=>..., 'top_customers'=>..., 'invoices'=>...]
    $cards = $cards ?? [];
    $trend = collect($trend ?? []);
    $top_products = collect($top_products ?? []);
    $top_customers = collect($top_customers ?? []);
    $invoices = collect($invoices ?? []);

    // Helper mini
    function rupiah($v)
    {
        return 'Rp ' . number_format((float) $v, 0, ',', '.');
    }

    function tgl($d)
    {
        return $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '-';
    }

    // Info filter (opsional): ambil dari request()->query()
    $q = request()->query();
    $from = $q['from'] ?? null;
    $to = $q['to'] ?? null;
    $status = $q['status'] ?? null;
    $sales = $q['sales_id'] ?? null;
    $cust = $q['customer_id'] ?? null;

    $rangeLabel = !$from && !$to ? 'Semua' : tgl($from) . ' - ' . tgl($to);
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan</title>
    <style>
        /* ===== Layout Halaman ===== */
        @page {
            margin: 90px 28px 70px 28px;
            /* top right bottom left */
            size: A4 {{ request('orientation') === 'landscape' ? 'landscape' : 'portrait' }};
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #222;
        }

        h1,
        h2,
        h3 {
            margin: 0 0 6px 0;
        }

        h1 {
            font-size: 18px;
        }

        h2 {
            font-size: 14px;
        }

        h3 {
            font-size: 12px;
            color: #444;
        }

        .muted {
            color: #666;
        }

        .small {
            font-size: 10px;
        }

        /* Header/Footer fixed */
        header {
            position: fixed;
            top: -70px;
            left: 0;
            right: 0;
            height: 70px;
        }

        footer {
            position: fixed;
            bottom: -50px;
            left: 0;
            right: 0;
            height: 50px;
            color: #666;
        }

        .header-wrap {
            border-bottom: 1px solid #ddd;
            padding-bottom: 6px;
            display: table;
            width: 100%;
        }

        .logo {
            display: table-cell;
            vertical-align: middle;
            width: 120px;
        }

        .logo img {
            max-height: 40px;
        }

        .title {
            display: table-cell;
            vertical-align: middle;
        }

        .title .sub {
            color: #666;
            margin-top: 2px;
        }

        .footer-wrap {
            border-top: 1px solid #ddd;
            padding-top: 6px;
            display: table;
            width: 100%;
        }

        .left {
            display: table-cell;
            text-align: left;
        }

        .right {
            display: table-cell;
            text-align: right;
        }

        /* Page number */
        .pagenum:before {
            content: counter(page);
        }

        .totalpages:before {
            content: counter(pages);
        }

        /* ===== Utilities ===== */
        .mb-2 {
            margin-bottom: 6px;
        }

        .mb-3 {
            margin-bottom: 10px;
        }

        .mb-4 {
            margin-bottom: 14px;
        }

        .mt-2 {
            margin-top: 6px;
        }

        .mt-3 {
            margin-top: 10px;
        }

        .row {
            width: 100%;
            display: table;
            table-layout: fixed;
        }

        .col {
            display: table-cell;
            vertical-align: top;
        }

        .col-3 {
            width: 25%;
        }

        .col-4 {
            width: 33.33%;
        }

        .col-6 {
            width: 50%;
        }

        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 3px;
            background: #f0f0f0;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .kpi {
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            padding: 8px;
        }

        .kpi h2 {
            font-size: 14px;
            margin: 0;
        }

        .kpi .val {
            font-size: 16px;
            font-weight: bold;
            margin-top: 4px;
        }

        /* ===== Table ===== */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            display: table-header-group;
        }

        /* repeat header on new page */
        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
        }

        /* prevent row split */
        th,
        td {
            padding: 6px 8px;
            border: 1px solid #e3e3e3;
        }

        th {
            background: #f7f7f7;
            font-weight: bold;
        }

        .nowrap {
            white-space: nowrap;
        }

        .money {
            text-align: right;
        }

        .table-compact th,
        .table-compact td {
            padding: 5px 6px;
        }

        .w-40 {
            width: 40%;
        }

        .w-10 {
            width: 10%;
        }

        .w-12 {
            width: 12%;
        }

        /* Section break */
        .section-title {
            font-size: 13px;
            font-weight: bold;
            margin: 12px 0 6px;
        }

        /* Info filter box */
        .filter-box {
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            padding: 6px 8px;
            background: #fbfbfb;
        }

        .filter-box .kv {
            display: inline-block;
            margin-right: 12px;
        }

        .kv .k {
            color: #666;
            margin-right: 4px;
        }
    </style>
    
</head>

<body>

    {{-- ===== Header ===== --}}
    <header>
        <div class="header-wrap">
            <div class="logo">
                {{-- pakai public_path agar DomPDF bisa baca file lokal --}}
                {{-- <img src="{{ public_path('logo.png') }}" alt="Logo"> --}}
            </div>
            <div class="title">
                <h1>Laporan Penjualan</h1>
                <div class="sub small">
                    Dicetak: {{ now()->format('d M Y H:i') }}
                </div>
            </div>
        </div>
    </header>

    {{-- ===== Footer ===== --}}
    <footer>
        <div class="footer-wrap small">
            <div class="left">Sellora • Laporan Penjualan</div>
            <div class="right">Hal. <span class="pagenum"></span> / <span class="totalpages"></span></div>
        </div>
    </footer>

    <main>
        {{-- ===== Ringkasan & Filter ===== --}}
        <div class="mb-3">
            <div class="filter-box small">
                <span class="kv"><span class="k">Rentang:</span> {{ $rangeLabel }}</span>
                @if ($status)
                    <span class="kv"><span class="k">Status:</span> {{ strtoupper($status) }}</span>
                @endif
                @if ($sales)
                    <span class="kv"><span class="k">Sales ID:</span> {{ $sales }}</span>
                @endif
                @if ($cust)
                    <span class="kv"><span class="k">Customer ID:</span> {{ $cust }}</span>
                @endif
            </div>
        </div>

        {{-- ===== KPI Cards ===== --}}
        <div class="row mb-4">
            <div class="col col-3">
                <div class="kpi">
                    <h2>Gross</h2>
                    <div class="val">{{ rupiah($cards['gross'] ?? 0) }}</div>
                </div>
            </div>
            <div class="col col-3">
                <div class="kpi">
                    <h2>Diskon</h2>
                    <div class="val">{{ rupiah($cards['discount'] ?? 0) }}</div>
                </div>
            </div>
            <div class="col col-3">
                <div class="kpi">
                    <h2>Retur</h2>
                    <div class="val">{{ rupiah($cards['return_total'] ?? 0) }}</div>
                </div>
            </div>
            <div class="col col-3">
                <div class="kpi">
                    <h2>Net Sales</h2>
                    <div class="val">{{ rupiah($cards['net_sales'] ?? 0) }}</div>
                </div>
            </div>
        </div>

        {{-- ===== Trend (tabel sederhana agar kompatibel PDF) ===== --}}
        @if ($trend->count())
            <div class="section-title">Trend Penjualan</div>
            <table class="table-compact">
                <thead>
                    <tr>
                        <th class="w-40">Tanggal</th>
                        <th class="money">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($trend as $t)
                        <tr>
                            <td class="nowrap">{{ tgl($t->d ?? ($t['d'] ?? null)) }}</td>
                            <td class="money">{{ rupiah($t->total ?? ($t['total'] ?? 0)) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- ===== Top Produk & Top Pelanggan (side-by-side) ===== --}}
        <div class="row mt-3">
            <div class="col col-6">
                <div class="section-title">Top Produk</div>
                <table class="table-compact">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="money w-12">Qty</th>
                            <th class="money w-40">Omzet</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($top_products as $p)
                            <tr>
                                <td>{{ $p->name ?? ($p['name'] ?? '-') }}</td>
                                <td class="money">
                                    {{ number_format((float) ($p->qty ?? ($p['qty'] ?? 0)), 0, ',', '.') }}
                                </td>
                                <td class="money">{{ rupiah($p->omzet ?? ($p['omzet'] ?? 0)) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center muted">Tidak ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="col col-6">
                <div class="section-title">Top Pelanggan</div>
                <table class="table-compact">
                    <thead>
                        <tr>
                            <th>Pelanggan</th>
                            <th class="money w-12">Transaksi</th>
                            <th class="money w-40">Omzet</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($top_customers as $cst)
                            <tr>
                                <td>{{ $cst->customer ?? ($cst['customer'] ?? '-') }}</td>
                                <td class="money">
                                    {{ number_format((int) ($cst->trx_count ?? ($cst['trx_count'] ?? 0)), 0, ',', '.') }}
                                </td>
                                <td class="money">{{ rupiah($cst->omzet ?? ($cst['omzet'] ?? 0)) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center muted">Tidak ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ===== Daftar Transaksi (bisa panjang, thead akan repeat) ===== --}}
        <div class="section-title mt-3">Detail Transaksi</div>
        <table>
            <thead>
                <tr>
                    <th class="w-12">Tanggal</th>
                    <th class="w-12">Nomor Invoice</th>
                    <th>Customer</th>
                    <th>Sales</th>
                    <th class="money w-12">Subtotal</th>
                    <th class="money w-10">Diskon</th>
                    <th class="money w-10">Retur</th>
                    <th class="money w-12">Total</th>
                    <th class="w-10">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $row)
                    <tr>
                        <td class="nowrap">{{ tgl($row->date ?? ($row['date'] ?? null)) }}</td>
                        <td class="nowrap">{{ $row->invoice_number ?? ($row['invoice_number'] ?? '-') }}</td>
                        <td>{{ $row->customer ?? ($row['customer'] ?? '-') }}</td>
                        <td>{{ $row->sales ?? ($row['sales'] ?? '-') }}</td>
                        <td class="money">{{ rupiah($row->subtotal ?? ($row['subtotal'] ?? 0)) }}</td>
                        <td class="money">{{ rupiah($row->discount ?? ($row['discount'] ?? 0)) }}</td>
                        <td class="money">{{ rupiah($row->return_total ?? ($row['return_total'] ?? 0)) }}</td>
                        <td class="money">{{ rupiah($row->total ?? ($row['total'] ?? 0)) }}</td>
                        <td class="nowrap text-center">
                            @switch($row->status ?? $row['status'] ?? '-')
                                @case('success')
                                    <span class="badge badge-sm bg-success">Lunas</span>
                                @break

                                @case('process')
                                    <span class="badge badge-sm bg-warning">Diproses</span>
                                @break

                                @default
                                    <span class="badge badge-sm bg-light">-</span>
                            @endswitch
                        </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center muted">Tidak ada data</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        </main>
    </body>

    </html>
