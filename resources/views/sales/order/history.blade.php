@extends('layouts.app')

@section('content')
    <div class="card bg-light-info shadow-none position-relative overflow-hidden">
        <div class="card-body px-4 py-3">
            <h4 class="fw-semibold mb-8">{{ $data['title'] ?? '' }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    @foreach ($data['breadcrumbs'] as $item)
                        @if ($loop->last)
                            <li class="breadcrumb-item active" aria-current="page">{{ $item['name'] }}</li>
                        @else
                            <li class="breadcrumb-item">
                                <a href="{{ $item['link'] }}" class="text-muted">{{ $item['name'] }}</a>
                            </li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Daftar Pesanan Yang Belum Diantar</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="orders-table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nomor Invoice</th>
                                        <th>Customer</th>
                                        <th>Tanggal Order</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data akan diisi oleh DataTables -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detail Pesanan -->
    <div class="modal fade" id="orderDetailModal" tabindex="-1" aria-labelledby="orderDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderDetailModalLabel">Detail Pesanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center" id="orderDetailLoading">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div id="orderDetailContent" style="display: none;">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6>Informasi Pesanan</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td>Nomor Invoice</td>
                                        <td>: <span class="ms-1" id="invoice-id"></span></td>
                                    </tr>
                                    <tr>
                                        <td>Tanggal Order</td>
                                        <td>: <span class="ms-1" id="order-date"></span></td>
                                    </tr>
                                    <tr>
                                        <td>Tanggal Invoice</td>
                                        <td>: <span class="ms-1" id="invoice-date"></span></td>
                                    </tr>
                                    <tr>
                                        <td>Status</td>
                                        <td>: <span class="ms-1" id="order-status"></span></td>
                                    </tr>
                                    <tr>
                                        <td>Admin Konfirmasi</td>
                                        <td>: <span class="ms-1" id="admin-confirm"></span></td>
                                    </tr>
                                    <tr>
                                        <td>Sales Agent</td>
                                        <td>: <span class="ms-1" id="sales-agent"></span></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Informasi Customer</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td>Nama</td>
                                        <td>: <span class="ms-1" id="customer-name"></span></td>
                                    </tr>
                                    <tr>
                                        <td>Telepon</td>
                                        <td>: <span class="ms-1" id="customer-phone"></span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <h6>Detail Item</h6>
                        <div class="table-responsive">
                            <table class="table table-sm" id="order-items-table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Produk</th>
                                        <th>Brand</th>
                                        <th>Jumlah</th>
                                        <th>Harga</th>
                                        <th>Diskon</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody id="order-items-body">
                                    <!-- Item pesanan akan diisi oleh JavaScript -->
                                </tbody>
                            </table>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3" id="note-container">
                                    <h6>Catatan</h6>
                                    <p id="order-note">-</p>
                                </div>
                                <div class="mb-3" id="cancel-note-container" style="display: none;">
                                    <h6>Alasan Pembatalan</h6>
                                    <p id="cancel-note">-</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <td>Total Awal</td>
                                        <td>: <span class="ms-1" id="initial-total"></span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Total Akhir</strong></td>
                                        <td>: <strong><span class="ms-1" id="final-total"></span></strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <label for="">Alamat Pengantaran</label>
                                <textarea name="" class="form-control" disabled id="customer-address"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary btn-tutup-detail-modal"
                        data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        $(function() {
            $('#orders-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('sales.orders.history.data') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'invoice_id',
                        name: 'invoice_id'
                    },
                    {
                        data: 'customer_name',
                        name: 'customer_name',
                        render: function(data, type, row) {
                            return '<div class="fw-semibold">' + data + '</div>' +
                                '<small class="text-muted">' + row.customer_phone + '</small>';
                        }
                    },
                    {
                        data: 'invoice_date',
                        name: 'invoice_date',
                    },
                    {
                        data: 'final_total_amount',
                        name: 'final_total_amount'
                    },
                    {
                        data: 'status_label',
                        name: 'transaction_status',
                        width: '120px'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    ['created_at', 'desc']
                ],
            });

            // Event handler untuk tombol lihat detail pesanan
            $(document).on('click', '.view-order', function() {
                const orderId = $(this).data('order-id');

                // Reset dan tampilkan loading
                $('#orderDetailContent').hide();
                $('#orderDetailLoading').show();
                $('#orderDetailModal').modal('show');
                $('.btn-konfirmasi-order').remove();

                // Ambil data detail pesanan dengan AJAX
                $.ajax({
                    url: `/sales/orders/${orderId}`,
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            const data = response.data;

                            // Isi data ke dalam modal
                            $('#invoice-id').text(data.invoice_id);
                            $('#order-date').text(data.order_date);
                            $('#invoice-date').text(data.invoice_date);
                            $('#order-status').html(data.status_label);
                            $('#admin-confirm').html(data.admin.name);

                            $('#customer-name').text(data.customer.name);
                            $('#customer-phone').text(data.customer.phone);
                            $('#customer-address').text(data.customer.address);
                            $('#sales-agent').text(data.sales_agent.name);
                            $('#initial-total').text(data.initial_total_amount);
                            $('#final-total').text(data.final_total_amount);

                            // Isi catatan jika ada
                            if (data.note) {
                                $('#order-note').text(data.note);
                                $('#note-container').show();
                            } else {
                                $('#note-container').hide();
                            }

                            // Tampilkan alasan pembatalan jika status cancelled
                            if (data.status === 'cancelled' && data.cancel_note) {
                                $('#cancel-note').text(data.cancel_note);
                                $('#cancel-note-container').show();
                            } else {
                                $('#cancel-note-container').hide();
                            }

                            // Isi tabel item pesanan
                            let itemsHtml = '';
                            data.items.forEach((item, index) => {
                                itemsHtml += `
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td>${item.product_name}</td>
                                        <td>${item.product_brand}</td>
                                        <td>${item.quantity} ${item.unit}</td>
                                        <td>${item.price}</td>
                                        <td>${Math.round(item.discount * 100)}%</td>
                                        <td>${item.subtotal}</td>
                                    </tr>
                                `;
                            });
                            $('#order-items-body').html(itemsHtml);

                            // Sembunyikan loading dan tampilkan konten
                            $('#orderDetailLoading').hide();
                            $('#orderDetailContent').show();
                        } else {
                            toastr.error('Gagal memuat data pesanan');
                            $('#orderDetailModal').modal('hide');
                        }
                    },
                    error: function() {
                        toastr.error('Terjadi kesalahan saat memuat data');
                        $('#orderDetailModal').modal('hide');
                    }
                });
            });
        });
    </script>
@endpush
