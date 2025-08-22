@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css"
        integrity="sha512-tS3S5qG0BlhnQROyJXvNjeEM4UpMXHrQfTGmbQ1gKmelCxlSEBUaxhRBj/EFTzpbP4RVSrpEikbmdJobCvhE3g=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
@endpush

@section('content')
    {{-- Transaction Carousel --}}
    <div class="row justify-content-md-between justify-content-center w-full">
        <div class="card border-0 zoom-in bg-light-primary shadow-none col-auto" style="width: 300px">
            <div class="card-body d-flex flex-column p-0">
                <select class="form-select ms-auto border-0 text-primary fw-bold" data-type="omset" id="omsetFilter"
                    style="width:auto" aria-label="Default select example">
                    <option value="all" selected>Semua</option>
                    <option value="day">Hari ini</option>
                    <option value="month">Bulan ini</option>
                    <option value="year">Tahun ini</option>
                </select>
                <div class="text-center mx-2 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="icon icon-tabler icon-tabler-cash mb-3 text-primary">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M7 15h-3a1 1 0 0 1 -1 -1v-8a1 1 0 0 1 1 -1h12a1 1 0 0 1 1 1v3" />
                        <path d="M7 9m0 1a1 1 0 0 1 1 -1h12a1 1 0 0 1 1 1v8a1 1 0 0 1 -1 1h-12a1 1 0 0 1 -1 -1z" />
                        <path d="M12 14a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                    </svg>
                    <p class="fw-semibold fs-3 text-primary mb-1"> Total Omset </p>
                    <h5 class="fw-semibold text-primary mb-0" id="omsetDisplay">
                        {{ 'Rp. ' . number_format($data['omset'], 2, ',', '.') }}</h5>
                    <small id="omsetComparison" class="d-block mt-1 text-muted"></small>
                </div>
            </div>
        </div>
        <div class="card border-0 zoom-in bg-light-danger shadow-none col-auto" style="width: 300px">
            <div class="card-body d-flex flex-column p-0">
                <select class="form-select ms-auto border-0 text-danger fw-bold" data-type="expense" id="expenseFilter"
                    style="width:auto" aria-label="Default select example">
                    <option value="all" selected>Semua</option>
                    <option value="day">Hari ini</option>
                    <option value="month">Bulan ini</option>
                    <option value="year">Tahun ini</option>
                </select>
                <div class="text-center mx-2 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="icon icon-tabler icon-tabler-cash-minus mb-3 text-danger">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M7 15h-3a1 1 0 0 1 -1 -1v-8a1 1 0 0 1 1 -1h12a1 1 0 0 1 1 1v3" />
                        <path d="M12 19h-4a1 1 0 0 1 -1 -1v-8a1 1 0 0 1 1 -1h12a1 1 0 0 1 1 1v5" />
                        <path d="M12 14a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                        <path d="M16 19h6" />
                    </svg>
                    <p class="fw-semibold fs-3 text-danger mb-1"> Pembelanjaan </p>
                    <h5 class="fw-semibold text-danger mb-0" id="expenseDisplay">
                        {{ 'Rp. ' . number_format($data['expense'], 2, ',', '.') }}</h5>
                    <small id="expenseComparison" class="d-block mt-1 text-muted"></small>
                </div>
            </div>
        </div>
        <div class="card border-0 zoom-in bg-light-success shadow-none col-auto" style="width: 300px">
            <div class="card-body d-flex flex-column p-0">
                <select class="form-select ms-auto border-0 text-success fw-bold" data-type="income" id="incomeFilter"
                    style="width:auto" aria-label="Default select example">
                    <option value="all" selected>Semua</option>
                    <option value="day">Hari ini</option>
                    <option value="month">Bulan ini</option>
                    <option value="year">Tahun ini</option>
                </select>
                <div class="text-center mx-2 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="icon icon-tabler icon-tabler-cash-plus mb-3 text-success">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M7 15h-3a1 1 0 0 1 -1 -1v-8a1 1 0 0 1 1 -1h12a1 1 0 0 1 1 1v3" />
                        <path d="M12 19h-4a1 1 0 0 1 -1 -1v-8a1 1 0 0 1 1 -1h12a1 1 0 0 1 1 1v2.5" />
                        <path d="M12 14a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                        <path d="M16 19h6" />
                        <path d="M19 16v6" />
                    </svg>
                    <p class="fw-semibold fs-3 text-success mb-1"> Pendapatan </p>
                    <h5 class="fw-semibold text-success mb-0" id="incomeDisplay">
                        {{ 'Rp. ' . number_format($data['income'], 2, ',', '.') }}</h5>
                    <small id="incomeComparison" class="d-block mt-1 text-muted"></small>
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
        <div class="col-lg-6 col-12 mb-3 mb-lg-0">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">5 Penjualan Terakhir</h5>
                    </div>
                    <ul class="list-group" id="latestSalesList"></ul>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-12">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">5 Pembelanjaan Terakhir</h5>
                    </div>
                    <ul class="list-group" id="latestExpenseList"></ul>
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
            function setComparisonText(type, comparison) {
                const map = {
                    omset: '#omsetComparison',
                    expense: '#expenseComparison',
                    income: '#incomeComparison'
                };
                const el = $(map[type]);
                if (!el.length) return;

                if (!comparison || comparison.change_percent === null) {
                    el.text('');
                    el.removeClass('text-success text-danger');
                    el.addClass('text-muted');
                    return;
                }

                const dir = comparison.direction;
                const pct = comparison.change_percent;
                const label = comparison.period_label || 'periode sebelumnya';
                let icon = '';
                let cls = 'text-muted';
                if (dir === 'up') {
                    icon = '▲';
                    cls = 'text-success';
                } else if (dir === 'down') {
                    icon = '▼';
                    cls = 'text-danger';
                } else {
                    icon = '■';
                    cls = 'text-muted';
                }
                el.removeClass('text-success text-danger text-muted');
                el.addClass(cls);
                el.text(`${icon} ${Math.abs(pct)}% dibanding ${label}`);
            }

            function fetchFilteredData(type, filter) {
                $.ajax({
                    url: "{{ route('owner.dashboard.data') }}",
                    method: "GET",
                    data: {
                        type: type,
                        filter: filter
                    },
                    success: function(response) {
                        console.log(response);
                        if (type === 'omset') {
                            $('#omsetDisplay').html('Rp. ' + response.omset);
                            setComparisonText(type, response.comparison);
                        } else if (type === 'expense') {
                            $('#expenseDisplay').html('Rp. ' + response.expense);
                            setComparisonText(type, response.comparison);
                        } else if (type === 'income') {
                            $('#incomeDisplay').html('Rp. ' + response.income);
                            setComparisonText(type, response.comparison);
                        }
                    },
                    error: function(xhr) {
                        console.log('Error fetching omset:', xhr.responseText);
                        alert('Gagal memuat data omset. Silakan coba lagi.');
                    }
                });
            }

            // Event listener untuk perubahan pada selectbox
            $('#omsetFilter').on('change', function() {
                var type = $(this).data('type');
                var filter = $(this).val(); // Dapatkan nilai filter yang dipilih
                fetchFilteredData(type, filter); // Panggil fungsi AJAX
            });
            $('#expenseFilter').on('change', function() {
                var type = $(this).data('type');
                var filter = $(this).val(); // Dapatkan nilai filter yang dipilih
                fetchFilteredData(type, filter); // Panggil fungsi AJAX
            });
            $('#incomeFilter').on('change', function() {
                var type = $(this).data('type');
                var filter = $(this).val(); // Dapatkan nilai filter yang dipilih
                fetchFilteredData(type, filter); // Panggil fungsi AJAX
            });

            // Biarkan default "Semua" (tanpa filter) agar kartu menampilkan keseluruhan data

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
                            },
                            {
                                label: 'Pembelanjaan',
                                data: expenseData,
                                borderColor: 'rgba(220,53,69,1)',
                                backgroundColor: 'rgba(220,53,69,0.10)',
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
                    url: "{{ route('owner.dashboard.sales_chart') }}",
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
                    url: "{{ route('owner.dashboard.top_sales') }}",
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
                    url: "{{ route('owner.dashboard.latest') }}",
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
