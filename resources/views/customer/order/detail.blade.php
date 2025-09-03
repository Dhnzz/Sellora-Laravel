<div class="row">
    <div class="col-12">
        {{-- Order Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h6 class="mb-1">Order <b>#{{ $orderData->order_number }}</b></h6>
                <small
                    class="text-muted">{{ \Carbon\Carbon::parse($orderData->order_date)->format('d M Y H:i') }}</small>
            </div>
            <div class="text-end">
                <div class="fw-semibold text-primary">Rp {{ number_format($orderData->total_amount, 0, ',', '.') }}</div>
                <div>{!! getStatusBadge($orderData->status) !!}</div>
            </div>
        </div>

        {{-- Admin & Sales Info --}}
        @if ($orderData->status !== 'pending' && $orderData->status !== 'cancelled')
            <div class="card c-card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="ti ti-user-check me-2"></i>Informasi Admin & Sales</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <small class="text-muted">Admin yang mengkonfirmasi:</small>
                            <div class="fw-semibold">
                                {{ $orderData->admin->name ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <small class="text-muted">Sales Agent Pengantar:</small>
                            <div class="fw-semibold">
                                {{ $orderData->sales_agent->name ?? 'N/A' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Customer Info --}}
        <div class="card c-card mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="ti ti-user me-2"></i>Informasi Pelanggan</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-2">
                            <small class="text-muted">Nama:</small>
                            <div class="fw-semibold">{{ $orderData->customer->name }}</div>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Email:</small>
                            <div>{{ $orderData->customer->email }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-2">
                            <small class="text-muted">Telepon:</small>
                            <div>{{ $orderData->customer->phone ?? '-' }}</div>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Alamat:</small>
                            <div>{{ $orderData->shipping_address ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Order Items --}}
        <div class="card c-card mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="ti ti-package me-2"></i>Item Pesanan</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Produk</th>
                                <th class="text-center">Harga</th>
                                <th class="text-center">Diskon Produk</th>
                                <th class="text-center">Harga Net</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($orderData->orderItems && count($orderData->orderItems) > 0)
                                @foreach ($orderData->orderItems as $item)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="{{ asset($item->product->image ?? 'uploads/images/products/default.jpg') }}"
                                                    alt="{{ $item->product->name }}" class="rounded me-3"
                                                    style="width: 50px; height: 50px; object-fit: cover;">
                                                <div>
                                                    <div class="fw-semibold">{{ $item->product->name }}</div>
                                                    <small
                                                        class="text-muted">{{ $item->product->brand->name ?? 'No Brand' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                        <td class="text-center" width="150px">
                                            {{ $item->product->discount > 0.0 ? $item->product->discount * 100 . '%' : '-' }}
                                        </td>
                                        <td class="text-center">Rp. {{ number_format($item->net_price, 0, ',', '.') }}
                                        </td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-end fw-semibold">Rp
                                            {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="ti ti-package-off me-2"></i>Tidak ada item
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Order Summary --}}
        <div class="card c-card mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="ti ti-calculator me-2"></i>Ringkasan Pembayaran</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-between fw-semibold">
                            <span>Total:</span>
                            <span class="text-primary">Rp
                                {{ number_format($orderData->total_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    function getStatusBadge($status)
    {
        $statusMap = [
            'pending' => '<span class="badge bg-warning">Pending</span>',
            'process' => '<span class="badge bg-info">Proses Pengantaran</span>',
            'cancelled' => '<span class="badge bg-danger">Dibatalkan</span>',
            'success' => '<span class="badge bg-success">Berhasil</span>',
        ];
        return $statusMap[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }
@endphp
