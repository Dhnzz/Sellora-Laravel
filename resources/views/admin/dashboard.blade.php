@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css"
        integrity="sha512-tS3S5qG0BlhnQROyJXvNjeEM4UpMXHrQfTGmbQ1gKmelCxlSEBUaxhRBj/EFTzpbP4RVSrpEikbmdJobCvhE3g=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
@endpush

@section('content')
    {{-- Transaction Carousel --}}
    <div class="row justify-content-md-between justify-content-center w-full">
        <div class="col-3">
            <div class="card">
                <div class="card-body p-3 rounded bg-primary d-flex align-items-center gap-3">
                    <i class="ti ti-clock fs-1 text-white" style="font-size: 2rem !important;"></i>
                    <div class="d-flex flex-column">
                        <p class="fw-semibold fs-3 text-white mb-1">Menunggu Konfirmasi</p>
                        <h2 class="fw-bold text-white mb-0" style="font-size: 2rem;">{{ $data['totalPending'] }}</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-3">
            <div class="card">
                <div class="card-body p-3 rounded bg-warning d-flex align-items-center gap-3">
                    <i class="ti ti-settings fs-1 text-white" style="font-size: 2rem !important;"></i>
                    <div class="d-flex flex-column">
                        <p class="fw-semibold fs-3 text-white mb-1">Sedang Diproses</p>
                        <h2 class="fw-bold text-white mb-0" style="font-size: 2rem;">{{ $data['totalProcess'] }}</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-3">
            <div class="card">
                <div class="card-body p-3 rounded bg-success d-flex align-items-center gap-3">
                    <i class="ti ti-check fs-1 text-white" style="font-size: 2rem !important;"></i>
                    <div class="d-flex flex-column">
                        <p class="fw-semibold fs-3 text-white mb-1">Pesanan Berhasil</p>
                        <h2 class="fw-bold text-white mb-0" style="font-size: 2rem;">{{ $data['totalSuccess'] }}</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-3">
            <div class="card">
                <div class="card-body p-3 rounded bg-danger d-flex align-items-center gap-3">
                    <i class="ti ti-x fs-1 text-white" style="font-size: 2rem !important;"></i>
                    <div class="d-flex flex-column">
                        <p class="fw-semibold fs-3 text-white mb-1">Pesanan Dibatalkan</p>
                        <h2 class="fw-bold text-white mb-0" style="font-size: 2rem;">{{ $data['totalCancelled'] }}</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Sales Line Chart dan Top Sales Pie Chart --}}
    <div class="row mt-4">
        <div class="col-lg-8 col-12 mb-3 mb-lg-0">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Trend Penjualan</h5>
                        <select class="form-select" id="salesChartRange" style="width:auto">
                            <option value="weekly" selected>Mingguan</option>
                            <option value="monthly">Bulanan</option>
                            <option value="yearly">Tahunan</option>
                        </select>
                    </div>
                    <div style="min-height: 320px;">
                        <canvas id="salesLineChart" height="320"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-12">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Top 5 Sales</h5>
                        <select class="form-select" id="topSalesRange" style="width:auto">
                            <option value="weekly" selected>Mingguan</option>
                            <option value="monthly">Bulanan</option>
                            <option value="yearly">Tahunan</option>
                        </select>
                    </div>
                    <div style="min-height: 320px;">
                        <canvas id="topSalesPie" height="320"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Latest Lists --}}
    <div class="row mt-4">
        <div class="col-12 mb-3 mb-lg-0">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">5 Penjualan Terakhir</h5>
                    </div>
                    <ul class="list-group" id="latestSalesList"></ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"
        integrity="sha512-bPs7Ae6pVvhOSiIcyUClR7/q2OAsRiovw4vAkX+zJbw3ShAeeqezq50RIIcIURq7Oa20rW2n2q+fyXBNcU9lrw=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>


    <script>
        $(document).ready(function() {
            // Inisialisasi dan handler untuk line chart menggunakan Chart.js
            var salesChart; // Chart.js instance
            function renderSalesChart(categories, series) {
                const ctx = document.getElementById('salesLineChart').getContext('2d');
                const salesData = (series && series[0]) ? series[0].data : [];
                const expenseData = (series && series[1]) ? series[1].data : [];
                if (salesChart) {
                    salesChart.destroy();
                }
                salesChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: categories,
                        datasets: [{
                                label: 'Penjualan',
                                data: salesData,
                                borderColor: 'rgba(13,110,253,1)',
                                backgroundColor: 'rgba(13,110,253,0.10)',
                                tension: 0.4,
                                fill: true,
                                borderWidth: 2,
                                pointRadius: 3,
                                pointHoverRadius: 4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const val = Number(context.parsed.y || 0);
                                        return 'Rp. ' + val.toLocaleString('id-ID', {
                                            minimumFractionDigits: 2
                                        });
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                ticks: {
                                    callback: function(value) {
                                        try {
                                            return 'Rp. ' + Number(value).toLocaleString('id-ID');
                                        } catch (_) {
                                            return value;
                                        }
                                    }
                                }
                            }
                        }
                    }
                });
            }

            function loadSalesChart(range) {
                $.ajax({
                    url: "{{ route('admin.dashboard.sales_chart') }}",
                    method: 'GET',
                    data: {
                        range: range
                    },
                    success: function(resp) {
                        renderSalesChart(resp.categories, resp.series);
                    },
                    error: function(xhr) {
                        console.log('Error load chart:', xhr.responseText);
                    }
                })
            }

            // pertama kali - mingguan
            loadSalesChart($('#salesChartRange').val());

            // on change
            $('#salesChartRange').on('change', function() {
                loadSalesChart($(this).val());
            });

            // Pie Chart Top Sales (Chart.js)
            var topSalesChart;

            function renderTopSales(labels, data) {
                const ctx = document.getElementById('topSalesPie').getContext('2d');
                if (topSalesChart) {
                    topSalesChart.destroy();
                }
                const palette = [
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)'
                ];
                const borders = [
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)'
                ];
                topSalesChart = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Jumlah Transaksi',
                            data: data,
                            backgroundColor: palette,
                            borderColor: borders,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const val = context.parsed;
                                        return label + ': ' + val + ' transaksi';
                                    }
                                }
                            }
                        }
                    }
                });
            }

            function loadTopSales(range) {
                $.ajax({
                    url: "{{ route('admin.dashboard.top_sales') }}",
                    method: 'GET',
                    data: {
                        range: range
                    },
                    success: function(resp) {
                        renderTopSales(resp.labels || [], resp.data || []);
                    },
                    error: function(xhr) {
                        console.log('Error load top sales:', xhr.responseText);
                    }
                })
            }

            // initial load
            loadTopSales($('#topSalesRange').val());
            $('#topSalesRange').on('change', function() {
                loadTopSales($(this).val());
            });

            // Latest lists (5 penjualan & 5 pembelanjaan)
            function loadLatest() {
                $.ajax({
                    url: "{{ route('admin.dashboard.latest') }}",
                    method: 'GET',
                    success: function(resp) {
                        const salesUl = $('#latestSalesList');
                        const expUl = $('#latestExpenseList');
                        salesUl.empty();
                        expUl.empty();

                        (resp.sales || []).forEach(function(item) {
                            salesUl.append(`
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold">${item.invoice_id}</div>
                                        <small class="text-muted">${item.date}${item.sales_agent ? ' • ' + item.sales_agent : ''}</small>
                                    </div>
                                    <span class="badge bg-primary-subtle text-primary">Rp. ${item.amount}</span>
                                </li>
                            `);
                        });

                        (resp.expenses || []).forEach(function(item) {
                            expUl.append(`
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold">${item.invoice_number}</div>
                                        <small class="text-muted">${item.date}${item.supplier ? ' • ' + item.supplier : ''}</small>
                                    </div>
                                    <span class="badge bg-danger-subtle text-danger">Rp. ${item.amount}</span>
                                </li>
                            `);
                        });
                    },
                    error: function(xhr) {
                        console.log('Error load latest lists:', xhr.responseText);
                    }
                });
            }

            loadLatest();

        });
    </script>
@endpush
