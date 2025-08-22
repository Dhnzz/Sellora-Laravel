@extends('layouts.app')

@section('title', 'Laporan Penjualan')

@section('content')
    <div class="container-fluid py-4">

        {{-- FILTER BAR --}}
        <div class="card mb-4">
            <div class="card-body">
                <form id="filterForm" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Rentang Tanggal</label>
                        <input type="text" id="daterange" class="form-control" autocomplete="off">
                        <input type="hidden" name="from" id="from">
                        <input type="hidden" name="to" id="to">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua</option>
                            <option value="success">Success</option>
                            <option value="process">Process</option>
                        </select>
                    </div>

                    <div class="col-12 d-flex gap-2 mt-2">
                        <button type="submit" class="btn btn-primary">Terapkan</button>
                        <button type="button" id="resetFilter" class="btn btn-outline-secondary">Reset</button>

                        <div class="ms-auto d-flex gap-1">
                            <a href="{{ route('owner.report.export.xlsx') }}" id="exportXlsx"
                                class="btn btn-sm btn-primary d-flex align-items-center gap-2">
                                <i class="ti ti-file-type-xls" style="font-size: 1.2em;"></i>
                                <span>Export XLSX</span>
                            </a>
                            <a href="{{ route('owner.report.export.csv') }}" id="exportCsv"
                                class="btn btn-sm btn-primary d-flex align-items-center gap-2">
                                <i class="ti ti-file-type-csv" style="font-size: 1.2em;"></i>
                                <span>Export CSV</span>
                            </a>
                            <a href="{{ route('owner.report.export.pdf') }}" id="exportPdf"
                                class="btn btn-sm btn-primary d-flex align-items-center gap-2">
                                <i class="ti ti-file-type-pdf" style="font-size: 1.2em;"></i>
                                <span>Export PDF</span>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- KPI CARDS --}}
        <div class="row g-3 mb-4 justify-content-center" id="kpiCards">
            <div class="col-6 col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Total Penjualan (Gross)</div>
                        <div class="fs-4 fw-semibold" id="kpiGross">-</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Diskon</div>
                        <div class="fs-4 fw-semibold" id="kpiDiscount">-</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Net Sales</div>
                        <div class="fs-4 fw-semibold" id="kpiNet">-</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Retur</div>
                        <div class="fs-4 fw-semibold" id="kpiReturn">-</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Jumlah Transaksi</div>
                        <div class="fs-4 fw-semibold" id="kpiTrx">-</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Rata-rata Per Transaksi</div>
                        <div class="fs-4 fw-semibold" id="kpiAov">-</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- CHARTS --}}
        <div class="row g-3 mb-4">
            <div class="col-lg-12">
                <div class="card h-100">
                    <div class="card-header py-2">Tren Penjualan</div>
                    <div class="card-body">
                        <div id="trendWrap" style="height:240px; position:relative;">
                            <canvas id="chartTrend"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            {{-- <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header py-2">Metode Pembayaran</div>
                    <div class="card-body">
                        <canvas id="chartMethod" style="120px"></canvas>
                    </div>
                </div>
            </div> --}}
        </div>

        {{-- TOP LISTS --}}
        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <span>Top Produk</span>
                        <select id="topProductLimit" class="form-select form-select-sm" style="width:auto;">
                            <option value="5">Top 5</option>
                            <option value="10" selected>Top 10</option>
                            <option value="20">Top 20</option>
                        </select>
                    </div>
                    <div class="card-body p-0 overflow-auto" style="height: 300px">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0" id="tblTopProducts">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Omzet</th>
                                    </tr>
                                </thead>
                                <tbody><!-- render by JS --></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <span>Top Pelanggan</span>
                        <select id="topCustomerLimit" class="form-select form-select-sm" style="width:auto;">
                            <option value="5">Top 5</option>
                            <option value="10" selected>Top 10</option>
                            <option value="20">Top 20</option>
                        </select>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0" id="tblTopCustomers">
                                <thead>
                                    <tr>
                                        <th>Pelanggan</th>
                                        <th class="text-end">Transaksi</th>
                                        <th class="text-end">Omzet</th>
                                    </tr>
                                </thead>
                                <tbody><!-- render by JS --></tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- DETAIL TRANSAKSI --}}
        <div class="card">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <span>Detail Transaksi</span>
                <div class="text-muted small" id="trxInfo">Menampilkan 0 baris</div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0" id="tblInvoices">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>No. Invoice</th>
                                <th>Pelanggan</th>
                                <th>Sales</th>
                                <th class="text-end">Total</th>
                                <th>Status</th>
                                <th>Opsi</th>
                            </tr>
                        </thead>
                        <tbody><!-- render by JS --></tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    {{-- MODAL DETAIL INVOICE --}}
    <div class="modal fade" id="modalInvoiceDetail" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Transaksi <span id="invNo" class="text-muted"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div id="invLoading" class="text-center py-4" style="display:none;">
                        <div class="spinner-border" role="status"></div>
                        <div class="mt-2">Memuat data...</div>
                    </div>

                    <div id="invError" class="alert alert-danger d-none"></div>

                    <div id="invContent" style="display:none;">
                        <div class="row g-3 mb-2">
                            <div class="col-md-4">
                                <div class="small text-muted mb-1">Tanggal</div>
                                <div id="invDate" class="fw-semibold">-</div>
                            </div>
                            <div class="col-md-4">
                                <div class="small text-muted mb-1">Customer</div>
                                <div id="invCustomer" class="fw-semibold">-</div>
                            </div>
                            <div class="col-md-4">
                                <div class="small text-muted mb-1">Sales</div>
                                <div id="invSales" class="fw-semibold">-</div>
                            </div>
                        </div>

                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered align-middle mb-0" id="tblInvItems">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produk</th>
                                        <th class="text-end" style="width:110px;">Qty</th>
                                        <th class="text-end" style="width:140px;">Harga</th>
                                        <th class="text-end" style="width:160px;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody><!-- diisi via JS --></tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end">Subtotal</th>
                                        <th class="text-end" id="fSubtotal">Rp 0</th>
                                    </tr>
                                    <tr>
                                        <th colspan="3" class="text-end">Diskon</th>
                                        <th class="text-end" id="fDiscount">Rp 0</th>
                                    </tr>
                                    <tr>
                                        <th colspan="3" class="text-end">Retur</th>
                                        <th class="text-end text-danger" id="fReturn">Rp 0</th>
                                    </tr>
                                    <tr class="table-secondary">
                                        <th colspan="3" class="text-end">Grand Total</th>
                                        <th class="text-end" id="fGrand">Rp 0</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div>
                            <span class="badge bg-secondary" id="invStatus">-</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a id="btnPrintPdf" class="btn btn-outline-secondary" target="_blank">Print PDF</a>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endpush

@push('scripts')
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script> --}}
    <script src="https://cdn.jsdelivr.net/npm/moment@2.30.1/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script> --}}

    <script>
        let dtInvoices;

        function initInvoicesTable() {
            if (dtInvoices) {
                dtInvoices.ajax.reload(); // kalau sudah ada, cukup reload
                return;
            }

            dtInvoices = $('#tblInvoices').DataTable({
                processing: true,
                serverSide: true,
                searching: true,
                lengthChange: true,
                pageLength: 10,
                order: [
                    [0, 'desc']
                ], // default sort: tanggal desc
                ajax: {
                    url: "{{ route('owner.report.invoices') }}",
                    data: function(d) {
                        // kirim juga filter dari form
                        const q = $('#filterForm').serializeArray().reduce((a, c) => (a[c.name] = c.value,
                            a), {});
                        d.from = q.from;
                        d.to = q.to;
                        d.sales_id = q.sales_id || '';
                        d.customer_id = q.customer_id || '';
                        d.status = q.status || '';
                        // DataTables sudah kirim draw/start/length/search/order sendiri
                    }
                },
                columns: [{
                        data: 'date',
                        name: 'date'
                    },
                    {
                        data: 'invoice_number',
                        name: 'invoice_number'
                    },
                    {
                        data: 'customer',
                        name: 'customer'
                    },
                    {
                        data: 'sales',
                        name: 'sales'
                    },
                    {
                        data: 'total',
                        name: 'total',
                        className: 'text-end',
                        render: d => 'Rp ' + Number(d || 0).toLocaleString('id-ID')
                    },
                    {
                        data: 'status',
                        name: 'status',
                        className: 'text-center',
                        render: s => {
                            switch ((s || '').toLowerCase()) {
                                case 'success':
                                    return '<span class="badge bg-success">Lunas</span>';
                                case 'process':
                                    return '<span class="badge bg-warning">Diproses</span>';
                                default:
                                    return '-';
                            }
                        }
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false,
                        render: (_, __, row) =>
                            `<button class="btn btn-sm btn-primary see-detail" data-id="${row.id}">Detail</button>`
                    }
                ],
                drawCallback: function() {
                    // update info jumlah baris (optional)
                    $('#trxInfo').text(
                        `Menampilkan ${this.api().page.info().recordsDisplay} dari ${this.api().page.info().recordsTotal} transaksi`
                    );
                }
            });
        }

        (function() {
            // ------- Utils -------
            const fmtIDR = n => (Number(n || 0)).toLocaleString('id-ID');

            function fmtDate(d) {
                if (!d) return '-';
                const dt = new Date(d);
                if (isNaN(dt)) return d; // biarkan apa adanya
                return dt.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });
            }


            // ------- Date Range -------
            const $dr = $('#daterange'),
                $from = $('#from'),
                $to = $('#to');

            // flag “semua”
            let isAll = false;

            function setRange(a, b, all = false) {
                isAll = !!all;
                if (isAll) {
                    $dr.val('Semua');
                    $from.val('');
                    $to.val('');
                    return;
                }
                $dr.val(a.format('DD MMM YYYY') + ' - ' + b.format('DD MMM YYYY'));
                $from.val(a.format('YYYY-MM-DD'));
                $to.val(b.format('YYYY-MM-DD'));
            }

            const initFrom = moment().startOf('month');
            const initTo = moment().endOf('day');

            $dr.daterangepicker({
                startDate: initFrom,
                endDate: initTo,
                autoUpdateInput: false, // biar kita kontrol sendiri teksnya
                ranges: {
                    'Semua': [moment('1900-01-01'), moment('2099-12-31')],
                    'Hari ini': [moment(), moment()],
                    'Kemarin': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Minggu ini': [moment().startOf('week'), moment().endOf('week')],
                    'Bulan ini': [moment().startOf('month'), moment().endOf('month')],
                    'Tahun ini': [moment().startOf('year'), moment().endOf('year')]
                },
                locale: {
                    format: 'DD/MM/YYYY'
                }
            }, function(start, end, label) {
                // callback saat pilih range
                if (label === 'Semua') {
                    setRange(null, null, true); // kosongkan from/to
                } else {
                    setRange(start, end, false); // set tanggal normal
                }
            });
            // set default (mis. Bulan ini). kalau mau default “Semua”, panggil setRange(null,null,true)
            setRange(null, null, true);

            // Saat user ketik manual/clear input, kalau dikosongkan anggap “Semua”
            $dr.on('cancel.daterangepicker', function() {
                setRange(null, null, true);
            });

            // ------- Charts Placeholder -------
            let chartTrend, chartMethod;

            function renderTrend(labels, values) {
                const old = Chart.getChart('chartTrend');
                if (old) old.destroy();

                const wrap = document.getElementById('trendWrap');
                wrap.innerHTML = '<canvas id="chartTrend"></canvas>'; // ganti canvas lama

                const ctx = document.getElementById('chartTrend').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                            data: values,
                            tension: .25,
                            pointRadius: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false, // canvas ikut tinggi parent
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: v => 'Rp ' + Number(v).toLocaleString('id-ID')
                                }
                            },
                            x: {
                                ticks: {
                                    autoSkip: true,
                                    maxRotation: 0
                                }
                            }
                        }
                    }
                });
            }

            // function renderMethod(labels, values) {
            //     const ctx = document.getElementById('chartMethod').getContext('2d');
            //     if (chartMethod) chartMethod.destroy();
            //     chartMethod = new Chart(ctx, {
            //         type: 'pie',
            //         data: {
            //             labels,
            //             datasets: [{
            //                 data: values
            //             }]
            //         },
            //         options: {
            //             responsive: true,
            //             maintainAspectRatio: false
            //         }
            //     });
            // }

            // ------- Table Renderers -------
            function renderTopProducts(rows) {
                const tbody = $('#tblTopProducts tbody').empty();
                rows.forEach(r => {
                    tbody.append(`<tr>
                        <td>${r.name||'-'}</td>
                        <td class="text-end">${fmtIDR(r.qty)}</td>
                        <td class="text-end">Rp ${fmtIDR(r.omzet)}</td>
                    </tr>`);
                });
            }

            function renderTopCustomers(rows) {
                const tbody = $('#tblTopCustomers tbody').empty();
                rows.forEach(r => {
                    tbody.append(`<tr>
                        <td>${r.customer||'-'}</td>
                        <td class="text-end">${fmtIDR(r.trx_count||0)}</td>
                        <td class="text-end">Rp ${fmtIDR(r.omzet||0)}</td>
                    </tr>`);
                });
            }

            // function renderInvoices(rows) {
            //     const tbody = $('#tblInvoices tbody').empty();
            //     $('#trxInfo').text(`Menampilkan ${rows.length} baris`);
            //     rows.forEach(r => {
            //         tbody.append(`<tr>
        //             <td>${r.date||'-'}</td>
        //             <td>${r.invoice_number||'-'}</td>
        //             <td>${r.customer||'-'}</td>
        //             <td>${r.sales||'-'}</td>
        //             <td class="text-end">Rp ${fmtIDR(r.total)}</td>
        //             <td class="text-center">${badgeByStatus(r.status)}</td>
        //             <td><button class="btn btn-sm btn-primary see-detail" data-id="${r.id}"><i class="ti ti-eye"></i></button></td>
        //         </tr>`);
            //     });
            // }
            initInvoicesTable();

            // function badgeByStatus(s) {
            //     switch ((s || '').toLowerCase()) {
            //         case 'success':
            //             return '<span class="badge bg-success">Lunas</span>';
            //         case 'process':
            //             return '<span class="badge bg-warning">Diproses</span>';
            //         default:
            //             return '-';
            //     }
            // }

            // ------- Fetch Data -------
            function buildQuery() {
                const params = $('#filterForm').serializeArray().reduce((a, c) => (a[c.name] = c.value, a), {});
                params.top_product_limit = $('#topProductLimit').val() || 10;
                params.top_customer_limit = $('#topCustomerLimit').val() || 10; // << baru

                // kalau 'Semua', hapus from/to biar backend ambil semua
                if ($dr.val() === 'Semua' || (!params.from && !params.to)) {
                    delete params.from;
                    delete params.to;
                }

                return params;
            }

            function applyExportLinks() {
                const q = new URLSearchParams(buildQuery()).toString();
                $('#exportXlsx').attr('href', `{{ route('owner.report.export.xlsx') }}?${q}`);
                $('#exportCsv').attr('href', `{{ route('owner.report.export.csv') }}?${q}`);
                $('#exportPdf').attr('href', `{{ route('owner.report.export.pdf') }}?${q}`);
            }

            function loadData() {
                const q = buildQuery();
                applyExportLinks();

                $.get("{{ route('owner.report.data') }}", q)
                    .done(function(res) {
                        // KPI
                        $('#kpiGross').text('Rp ' + fmtIDR(res.cards?.gross));
                        $('#kpiDiscount').text('Rp ' + fmtIDR(res.cards?.discount));
                        $('#kpiTax').text('Rp ' + fmtIDR(res.cards?.tax));
                        $('#kpiReturn').text('Rp ' + fmtIDR(res.cards?.return_total));
                        $('#kpiNet').text('Rp ' + fmtIDR(res.cards?.net_sales));
                        $('#kpiTrx').text(fmtIDR(res.cards?.trx_count));
                        $('#kpiAov').text('Rp ' + fmtIDR(res.cards?.aov));

                        // Charts
                        const trendLabels = (res.trend || []).map(x => x.d);
                        const trendValues = (res.trend || []).map(x => x.total);
                        renderTrend(trendLabels, trendValues);

                        // Tables
                        renderTopProducts(res.top_products || []);
                        renderTopCustomers(res.top_customers || []);
                        // renderInvoices(res.invoices || []);
                    })
                    .fail(function(xhr) {
                        toastr.error('Gagal mengambil data laporan');
                        console.log(xhr.responseText || xhr);
                    });
            }

            function openInvoiceDetail(id) {
                // reset UI
                $('#invNo').text('#' + id);
                $('#invError').addClass('d-none').text('');
                $('#invContent').hide();
                $('#invLoading').show();

                const modal = new bootstrap.Modal(document.getElementById('modalInvoiceDetail'));
                modal.show();

                $.get("{{ route('owner.report.invoice.show', ':id') }}".replace(':id', id))
                    .done(function(res) {
                        const h = res.header || {};
                        const items = res.items || [];

                        // header
                        $('#invDate').text(fmtDate(h.date));
                        $('#invCustomer').text(h.customer || '-');
                        $('#invSales').text(h.sales || '-');
                        $('#invStatus').text((h.status || '-').toUpperCase())
                            .removeClass('bg-secondary bg-success bg-warning')
                            .addClass(
                                (h.status || '').toLowerCase() === 'success' ? 'bg-success' :
                                (h.status || '').toLowerCase() === 'process' ? 'bg-warning' : 'bg-secondary'
                            );

                        // items
                        const tbody = $('#tblInvItems tbody').empty();
                        if (items.length === 0) {
                            tbody.append(
                                '<tr><td colspan="4" class="text-center text-muted">Tidak ada item</td></tr>');
                        } else {
                            items.forEach(it => {
                                tbody.append(`
                                        <tr>
                                            <td>${(it.name||'-')}</td>
                                            <td class="text-end">${Number(it.qty||0).toLocaleString('id-ID')}</td>
                                            <td class="text-end">${fmtIDR(it.price||0)}</td>
                                            <td class="text-end">${fmtIDR(it.line_total||0)}</td>
                                        </tr>
                                        `);
                            });
                        }

                        // footer totals
                        $('#fSubtotal').text(fmtIDR(h.subtotal || 0));
                        $('#fDiscount').text(fmtIDR(h.discount || 0));
                        $('#fReturn').text(fmtIDR(h.return || 0));
                        // grand total: total (setelah diskon) - retur
                        const grand = Number(h.total || 0) - Number(h.return || 0);
                        $('#fGrand').text(fmtIDR(grand));

                        // tombol print PDF (optional: kirim filter lain kalau mau)
                        const q = new URLSearchParams({
                            stream: 1,
                            orientation: 'portrait'
                        }).toString();
                        $('#btnPrintPdf').attr('href', `{{ route('owner.report.export.pdf') }}?${q}`);

                        $('#btnPrintPdf').attr('href', `{{ route('owner.report.invoice.pdf', ':id') }}`
                            .replace(':id', id) + '?stream=1');

                        $('#invLoading').hide();
                        $('#invContent').show();
                    })
                    .fail(function(xhr) {
                        $('#invLoading').hide();
                        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message :
                            'Gagal memuat detail.';
                        $('#invError').removeClass('d-none').text(msg);
                    });
            }

            // ------- Events -------
            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                loadData();
                dtInvoices.ajax.reload();
            });

            $('#resetFilter').on('click', function() {
                $('#filterForm').trigger('reset');
                setRange(null, null, true); // set ke "Semua"
                loadData();
                dtInvoices.ajax.reload();
            });

            $('#topProductLimit').on('change', loadData);
            $('#topCustomerLimit').on('change', loadData);

            // Detail modal (placeholder)
            $(document).on('click', '.see-detail', function() {
                const id = $(this).data('id');
                openInvoiceDetail(id);
            });

            // Init pertama kali
            loadData();
        })();
    </script>
@endpush
