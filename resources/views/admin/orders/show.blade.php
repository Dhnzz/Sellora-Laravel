@extends('layouts.app')

@section('title', 'Detail Pesanan #' . $order->id . ' – Admin')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Detail Pesanan #{{ $order->id }}</h4>
                            <div>
                                <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">
                                    <i class="ti ti-arrow-left"></i> Kembali
                                </a>
                                @if ($order->status === 'pending')
                                    <button class="btn btn-success confirm-order" data-order-id="{{ $order->id }}">
                                        <i class="ti ti-check"></i> Konfirmasi
                                    </button>
                                    <button class="btn btn-danger cancel-order" data-order-id="{{ $order->id }}">
                                        <i class="ti ti-x"></i> Batalkan
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Informasi Customer</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="150">Nama</td>
                                        <td>: {{ $order->customer->name }}</td>
                                    </tr>
                                    <tr>
                                        <td>Telepon</td>
                                        <td>: {{ $order->customer->phone }}</td>
                                    </tr>
                                    <tr>
                                        <td>Alamat</td>
                                        <td>: {{ $order->customer->address }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Informasi Pesanan</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="150">Status</td>
                                        <td>:
                                            @if ($order->status === 'pending')
                                                <span class="badge bg-warning">Menunggu Konfirmasi</span>
                                            @elseif($order->status === 'confirmed')
                                                <span class="badge bg-success">Dikonfirmasi</span>
                                            @elseif($order->status === 'cancelled')
                                                <span class="badge bg-danger">Dibatalkan</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Tanggal Order</td>
                                        <td>: {{ $order->order_date->format('d/m/Y H:i') }}</td>
                                    </tr>
                                    <tr>
                                        <td>Tanggal Pengiriman</td>
                                        <td>: {{ $order->delivery_date->format('d/m/Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td>Catatan</td>
                                        <td>: {{ $order->note ?: '-' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <hr>

                        <h6>Item Pesanan</h6>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Brand</th>
                                        <th>Harga Satuan</th>
                                        <th>Jumlah</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $total = 0;
                                    @endphp
                                    @foreach ($order->purchase_order_items as $item)
                                        @php
                                            $subtotal = $item->quantity_ordered * $item->unit_price;
                                            $total += $subtotal;
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $item->product->name }}</div>
                                            </td>
                                            <td>{{ $item->product->product_brand->name }}</td>
                                            <td>Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                            <td>{{ $item->quantity_ordered }}</td>
                                            <td>Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="fw-semibold">
                                        <td colspan="4" class="text-end">Total</td>
                                        <td>Rp {{ number_format($total, 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        @if ($order->status === 'confirmed')
                            <div class="alert alert-success mt-3">
                                <i class="ti ti-check-circle"></i>
                                Pesanan ini sudah dikonfirmasi dan Sales Transaction telah dibuat.
                            </div>
                        @elseif($order->status === 'cancelled')
                            <div class="alert alert-danger mt-3">
                                <i class="ti ti-x-circle"></i>
                                Pesanan ini telah dibatalkan.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            $('.confirm-order').on('click', function() {
                const orderId = $(this).data('order-id');

                if (confirm(
                        'Yakin ingin mengkonfirmasi pesanan ini? Ini akan membuat Sales Transaction.')) {
                    $.ajax({
                        url: `/admin/orders/${orderId}/confirm`,
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                location.reload();
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function() {
                            toastr.error('Terjadi kesalahan');
                        }
                    });
                }
            });

            $('.cancel-order').on('click', function() {
                const orderId = $(this).data('order-id');

                if (confirm('Yakin ingin membatalkan pesanan ini?')) {
                    $.ajax({
                        url: `/admin/orders/${orderId}/cancel`,
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                location.reload();
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function() {
                            toastr.error('Terjadi kesalahan');
                        }
                    });
                }
            });
        });
    </script>
@endpush
