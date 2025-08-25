@extends('layouts.customer.app')

@section('title', 'Pesanan Saya – Sellora')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="mb-0">Pesanan Saya</h4>
                <a href="{{ route('customer.catalog') }}" class="btn btn-outline-primary">
                    <i class="ti ti-plus me-1"></i> Belanja Lagi
                </a>
            </div>

            {{-- Filter Section --}}
            <div class="card c-card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Status Pesanan</label>
                            <select id="statusFilter" class="form-select form-select-sm">
                                <option value="">Semua Status</option>
                                <option value="success">Sukses</option>
                                <option value="process">Diproses</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Periode</label>
                            <select id="periodFilter" class="form-select form-select-sm">
                                <option value="">Semua Periode</option>
                                <option value="7">7 Hari Terakhir</option>
                                <option value="30">30 Hari Terakhir</option>
                                <option value="90">3 Bulan Terakhir</option>
                                <option value="365">1 Tahun Terakhir</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Urutkan</label>
                            <select id="sortFilter" class="form-select form-select-sm">
                                <option value="latest">Terbaru</option>
                                <option value="oldest">Terlama</option>
                                <option value="highest">Harga Tertinggi</option>
                                <option value="lowest">Harga Terendah</option>
                            </select>
                        </div>
                        {{-- <div class="col-md-3">
                            <label class="form-label small fw-semibold">Cari</label>
                            <div class="input-group input-group-sm">
                                <input type="text" id="searchFilter" class="form-control" placeholder="Cari pesanan...">
                                <button class="btn btn-outline-secondary" type="button" id="clearFilter">
                                    <i class="ti ti-x"></i>
                                </button>
                            </div>
                        </div> --}}
                    </div>
                </div>
            </div>

            {{-- Loading Spinner --}}
            <div id="loadingSpinner" class="text-center py-5 d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Memuat pesanan...</p>
            </div>

            {{-- Orders List Container --}}
            <div id="ordersContainer">
                {{-- Orders will be loaded here via AJAX --}}
            </div>

            {{-- Empty State --}}
            <div id="emptyState" class="text-center py-5 d-none">
                <i class="ti ti-package-off" style="font-size: 4rem; color: #d1d5db;"></i>
                <h5 class="mt-3 text-muted">Belum ada pesanan</h5>
                <p class="text-muted">Mulai berbelanja untuk melihat pesanan Anda di sini</p>
                <a href="{{ route('customer.catalog') }}" class="btn btn-primary">
                    <i class="ti ti-shopping-cart me-1"></i> Mulai Belanja
                </a>
            </div>

            {{-- Pagination --}}
            <div id="paginationContainer" class="d-flex justify-content-center mt-4 d-none">
                {{-- Pagination will be loaded here via AJAX --}}
            </div>
        </div>
    </div>

    {{-- Order Detail Modal --}}
    <div class="modal fade" id="orderDetailModal" tabindex="-1" aria-labelledby="orderDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderDetailModalLabel">Detail Pesanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="orderDetailContent">
                    {{-- Order detail content will be loaded here --}}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" id="printOrder">
                        <i class="ti ti-printer me-1"></i> Cetak
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            let currentPage = 1;
            let isLoading = false;

            // Load orders on page load
            loadOrders();

            // Filter change events
            $('#statusFilter, #periodFilter, #sortFilter').on('change', function() {
                currentPage = 1;
                loadOrders();
            });

            // Search with debounce
            let searchTimeout;
            $('#searchFilter').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    currentPage = 1;
                    loadOrders();
                }, 500);
            });

            // Clear filter
            $('#clearFilter').on('click', function() {
                $('#statusFilter, #periodFilter, #sortFilter').val('');
                $('#searchFilter').val('');
                currentPage = 1;
                loadOrders();
            });

            // Load orders function
            function loadOrders() {
                if (isLoading) return;

                isLoading = true;
                showLoading(true);

                const filters = {
                    status: $('#statusFilter').val(),
                    period: $('#periodFilter').val(),
                    sort: $('#sortFilter').val(),
                    search: $('#searchFilter').val(),
                    page: currentPage
                };

                $.ajax({
                    url: '{{ route('customer.order.data') }}',
                    method: 'GET',
                    data: filters,
                    success: function(response) {
                        if (response.success) {
                            renderOrders(response.data.orders);
                            renderPagination(response.data.pagination);
                            showEmptyState(response.data.orders.length === 0);
                        } else {
                            showError('Gagal memuat data pesanan');
                        }
                    },
                    error: function() {
                        showError('Terjadi kesalahan saat memuat data');
                    },
                    complete: function() {
                        isLoading = false;
                        showLoading(false);
                    }
                });
            }

            // Render orders
            function renderOrders(orders) {
                const container = $('#ordersContainer');

                if (orders.length === 0) {
                    container.html('');
                    return;
                }

                let html = '';
                orders.forEach(function(order) {
                    html += `
                <div class="card c-card mb-3 order-item" data-order-id="${order.id}">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <div class="text-center">
                                    <div class="small text-muted mb-1">Invoice</div>
                                    <div class="fw-semibold">#${order.order_number}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="small text-muted mb-1">Tanggal Order</div>
                                <div>${formatDate(order.order_date)}</div>
                            </div>
                            <div class="col-md-2">
                                <div class="small text-muted mb-1">Total</div>
                                <div class="fw-semibold text-primary">Rp ${formatNumber(order.total_amount)}</div>
                            </div>
                            <div class="col-md-2">
                                <div class="small text-muted mb-1">Status</div>
                                <div>${getStatusBadge(order.status)}</div>
                            </div>
                            <div class="col-md-3 text-end">
                                <button class="btn btn-outline-primary btn-sm view-detail" data-order-id="${order.id}">
                                    <i class="ti ti-eye me-1"></i> Detail
                                </button>
                                ${order.status === 'delivered' ? `
                                                <button class="btn btn-success btn-sm ms-1" onclick="reviewOrder(${order.id})">
                                                    <i class="ti ti-star me-1"></i> Review
                                                </button>
                                            ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
                });

                container.html(html);
            }

            // Render pagination
            function renderPagination(pagination) {
                const container = $('#paginationContainer');

                if (!pagination || pagination.last_page <= 1) {
                    container.addClass('d-none');
                    return;
                }

                let html = '<nav><ul class="pagination">';

                // Previous button
                html += `
            <li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pagination.current_page - 1}">
                    <i class="ti ti-chevron-left"></i>
                </a>
            </li>
        `;

                // Page numbers
                for (let i = 1; i <= pagination.last_page; i++) {
                    if (i === 1 || i === pagination.last_page ||
                        (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
                        html += `
                    <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
                    } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
                        html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }

                // Next button
                html += `
            <li class="page-item ${pagination.current_page === pagination.last_page ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pagination.current_page + 1}">
                    <i class="ti ti-chevron-right"></i>
                </a>
            </li>
        `;

                html += '</ul></nav>';
                container.html(html).removeClass('d-none');

                // Pagination click events
                container.find('.page-link').on('click', function(e) {
                    e.preventDefault();
                    const page = $(this).data('page');
                    if (page && page !== currentPage) {
                        currentPage = page;
                        loadOrders();
                    }
                });
            }

            // View order detail
            $(document).on('click', '.view-detail', function() {
                const orderId = $(this).data('order-id');
                loadOrderDetail(orderId);
            });

            // Load order detail
            function loadOrderDetail(orderId) {
                $.ajax({
                    url: '{{ url('customer/order') }}/' + orderId,
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            $('#orderDetailContent').html(response.html);
                            $('#orderDetailModal').modal('show');
                        } else {
                            showError('Gagal memuat detail pesanan');
                        }
                    },
                    error: function() {
                        showError('Terjadi kesalahan saat memuat detail');
                    }
                });
            }

            // Utility functions
            function formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });
            }

            function formatNumber(number) {
                return new Intl.NumberFormat('id-ID').format(number);
            }

            function getStatusBadge(status) {
                const statusMap = {
                    'success': '<span class="badge bg-success">Sukses</span>',
                    'process': '<span class="badge bg-warning">Diproses</span>',
                };
                return statusMap[status] || '<span class="badge bg-secondary">Unknown</span>';
            }

            function showLoading(show) {
                if (show) {
                    $('#loadingSpinner').removeClass('d-none');
                    $('#ordersContainer').addClass('d-none');
                } else {
                    $('#loadingSpinner').addClass('d-none');
                    $('#ordersContainer').removeClass('d-none');
                }
            }

            function showEmptyState(show) {
                if (show) {
                    $('#emptyState').removeClass('d-none');
                    $('#ordersContainer').addClass('d-none');
                    $('#paginationContainer').addClass('d-none');
                } else {
                    $('#emptyState').addClass('d-none');
                    $('#ordersContainer').removeClass('d-none');
                }
            }

            function showError(message) {
                toastr.error(message);
            }
        });

        // Review order function (global)
        function reviewOrder(orderId) {
            // Implement review functionality
            alert('Fitur review akan segera hadir!');
        }
    </script>
@endpush
