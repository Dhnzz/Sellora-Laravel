@php
    $h = $header ?? [];
    $items = collect($items ?? []);
    function rupiah($v)
    {
        return 'Rp ' . number_format((float) $v, 0, ',', '.');
    }
    function tgl($d)
    {
        return $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '-';
    }
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $h['invoice_number'] ?? '-' }}</title>
    <style>
        @page {
            margin: 60px 28px 50px 28px;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #222;
        }

        h1,
        h2,
        h3 {
            margin: 0;
        }

        h1 {
            font-size: 18px;
            margin-bottom: 4px;
        }

        .small {
            font-size: 10px;
            color: #666;
        }

        header {
            position: fixed;
            top: -40px;
            left: 0;
            right: 0;
            height: 40px;
        }

        footer {
            position: fixed;
            bottom: -40px;
            left: 0;
            right: 0;
            height: 40px;
            color: #666;
            font-size: 10px;
        }

        .pagenum:before {
            content: counter(page);
        }

        .totalpages:before {
            content: counter(pages);
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

        .col-6 {
            width: 50%;
        }

        .box {
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            padding: 8px;
        }

        .kv .k {
            color: #666;
            width: 90px;
            display: inline-block;
        }

        .kv .v {
            color: #222;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #e3e3e3;
            padding: 6px 8px;
        }

        th {
            background: #f7f7f7;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .nowrap {
            white-space: nowrap;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            background: #eee;
            font-size: 10px;
        }

        .mt-2 {
            margin-top: 8px;
        }

        .mb-2 {
            margin-bottom: 8px;
        }
    </style>
</head>

<body>
    <header>
        <div style="border-bottom:1px solid #ddd; padding-bottom:6px;">
            <h1>Invoice #{{ $h['invoice_number'] ?? '-' }}</h1>
            <div class="small">Dicetak: {{ now()->format('d M Y H:i') }}</div>
        </div>
    </header>

    <footer>
        <div style="border-top:1px solid #ddd; padding-top:6px;">
            <span>Sellora • Invoice</span>
            <span style="float:right">Hal. <span class="pagenum"></span> / <span class="totalpages"></span></span>
        </div>
    </footer>

    <main style="margin-top: 3em">
        {{-- Header info --}}
        <div class="box">
            <div class="row mb-2" style="">
                <div class="col col-6">
                    <div class="kv"><span class="k">Tanggal</span><span class="v">:
                            {{ tgl($h['date'] ?? null) }}</span></div><br>
                    <div class="kv"><span class="k">Customer</span><span class="v">:
                            {{ $h['customer'] ?? '-' }}</span></div><br>
                    <div class="kv"><span class="k">Sales</span><span class="v">:
                            {{ $h['sales'] ?? '-' }}</span></div><br>
                </div>

                <div class="col col-6">
                    <div class="kv"><span class="k">Status</span>
                        <span class="v">: <span class="badge">{{ strtoupper($h['status'] ?? '-') }}</span></span>
                    </div><br>
                    <div class="kv"><span class="k">No. Invoice</span><span class="v">:
                            #{{ $h['invoice_number'] ?? '-' }}</span></div><br>
                </div>
            </div>
        </div>

        {{-- Items --}}
        <table>
            <thead>
                <tr>
                    <th>Produk</th>
                    <th class="text-center" style="width:80px;">Qty</th>
                    <th class="text-right" style="width:120px;">Harga</th>
                    <th class="text-right" style="width:140px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $it)
                    <tr>
                        <td>{{ $it->name ?? '-' }}</td>
                        <td class="text-center">{{ number_format((float) ($it->qty ?? 0), 0, ',', '.') }}</td>
                        <td class="text-right">{{ rupiah($it->price ?? 0) }}</td>
                        <td class="text-right">{{ rupiah($it->line_total ?? 0) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center small">Tidak ada item.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-right">Subtotal</th>
                    <th class="text-right">{{ rupiah($h['subtotal'] ?? 0) }}</th>
                </tr>
                <tr>
                    <th colspan="3" class="text-right">Diskon</th>
                    <th class="text-right">{{ rupiah($h['discount'] ?? 0) }}</th>
                </tr>
                <tr>
                    <th colspan="3" class="text-right">Pajak</th>
                    <th class="text-right">{{ rupiah($h['tax'] ?? 0) }}</th>
                </tr>
                <tr>
                    <th colspan="3" class="text-right">Biaya Lain</th>
                    <th class="text-right">{{ rupiah($h['other'] ?? 0) }}</th>
                </tr>
                <tr>
                    <th colspan="3" class="text-right">Retur</th>
                    <th class="text-right" style="color:#b42318;">{{ rupiah($h['return'] ?? 0) }}</th>
                </tr>
                <tr>
                    <th colspan="3" class="text-right">Grand Total</th>
                    <th class="text-right">{{ rupiah($h['grand'] ?? ($h['total'] ?? 0)) }}</th>
                </tr>
            </tfoot>
        </table>

        <div class="small mt-2">* Grand Total = Total (setelah diskon) − Retur</div>
    </main>
</body>

</html>
F
