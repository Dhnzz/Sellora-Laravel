@extends('layouts.app')

@section('title', 'Kelola Pesanan – Admin')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Daftar Pesanan Customer</h4>
                    </div>
                    <div class="card-body">
                        @if ($orders->isEmpty())
                            <div class="text-center py-5">
                                <i class="ti ti-shopping-cart" style="font-size: 4rem; color: #ccc;"></i>
                                <h6 class="mt-3 text-muted">Belum ada pesanan</h6>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Customer</th>
                                            <th>Tanggal Order</th>
                                            <th>Tanggal Pengiriman</th>
                                            <th>Total Item</th>
                                            <th>Total Harga</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($orders as $order)
                                            @php
                                                $totalItems = $order->purchase_order_items->sum('quantity_ordered');
                                                $totalPrice = $order->purchase_order_items->sum(function ($item) {
                                                    return $item->quantity_ordered * $item->unit_price;
                                                });
                                            @endphp
                                            <tr>
                                                <td>#{{ $order->id }}</td>
                                                <td>
                                                    <div class="fw-semibold">{{ $order->customer->name }}</div>
                                                    <small class="text-muted">{{ $order->customer->phone }}</small>
                                                </td>
                                                <td>{{ $order->order_date->format('d/m/Y H:i') }}</td>
                                                <td>{{ $order->delivery_date->format('d/m/Y') }}</td>
                                                <td>{{ $totalItems }} item</td>
                                                <td>Rp {{ number_format($totalPrice, 0, ',', '.') }}</td>
                                                <td>
                                                    @if ($order->status === 'pending')
                                                        <span class="badge bg-warning">Menunggu Konfirmasi</span>
                                                    @elseif($order->status === 'confirmed')
                                                        <span class="badge bg-success">Dikonfirmasi</span>
                                                    @elseif($order->status === 'cancelled')
                                                        <span class="badge bg-danger">Dibatalkan</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="{{ route('admin.orders.show', $order->id) }}"
                                                            class="btn btn-outline-primary">
                                                            <i class="ti ti-eye"></i>
                                                        </a>
                                                        @if ($order->status === 'pending')
                                                            <button class="btn btn-outline-success confirm-order"
                                                                data-order-id="{{ $order->id }}">
                                                                <i class="ti ti-check"></i>
                                                            </button>
                                                            <button class="btn btn-outline-danger cancel-order"
                                                                data-order-id="{{ $order->id }}">
                                                                <i class="ti ti-x"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3">
                                {{ $orders->links() }}
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
                const btn = $(this);

                if (confirm(
                        'Yakin ingin mengkonfirmasi pesanan ini? Ini akan membuat Sales Transaction.')) {
                    btn.prop('disabled', true);

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
                        },
                        complete: function() {
                            btn.prop('disabled', false);
                        }
                    });
                }
            });

            $('.cancel-order').on('click', function() {
                const orderId = $(this).data('order-id');
                const btn = $(this);

                if (confirm('Yakin ingin membatalkan pesanan ini?')) {
                    btn.prop('disabled', true);

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
                        },
                        complete: function() {
                            btn.prop('disabled', false);
                        }
                    });
                }
            });
        });
    </script>
@endpush
