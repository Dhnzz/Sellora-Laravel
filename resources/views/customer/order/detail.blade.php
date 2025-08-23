<div class="row">
    <div class="col-12">
        {{-- Order Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h6 class="mb-1">Order #{{ $orderData->order_number }}</h6>
                <small
                    class="text-muted">{{ \Carbon\Carbon::parse($orderData->created_at)->format('d M Y H:i') }}</small>
            </div>
            <div class="text-end">
                <div class="fw-semibold text-primary">Rp {{ number_format($orderData->total_amount, 0, ',', '.') }}</div>
                <div>{!! getStatusBadge($orderData->status) !!}</div>
            </div>
        </div>

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
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Produk</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Harga</th>
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
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-end">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
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
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>Rp {{ number_format($orderData->subtotal, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Ongkos Kirim:</span>
                            <span>Rp {{ number_format($orderData->shipping_cost ?? 0, 0, ',', '.') }}</span>
                        </div>
                        @if ($orderData->discount_amount > 0)
                            <div class="d-flex justify-content-between mb-2 text-success">
                                <span>Diskon:</span>
                                <span>- Rp {{ number_format($orderData->discount_amount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <hr>
                        <div class="d-flex justify-content-between fw-semibold">
                            <span>Total:</span>
                            <span class="text-primary">Rp
                                {{ number_format($orderData->total_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-2">
                            <small class="text-muted">Metode Pembayaran:</small>
                            <div class="fw-semibold">{{ $orderData->payment_method ?? 'Transfer Bank' }}</div>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Status Pembayaran:</small>
                            <div>{!! getPaymentStatusBadge($orderData->payment_status ?? 'pending') !!}</div>
                        </div>
                        @if ($orderData->payment_proof)
                            <div class="mb-2">
                                <small class="text-muted">Bukti Pembayaran:</small>
                                <div>
                                    <a href="{{ asset($orderData->payment_proof) }}" target="_blank"
                                        class="btn btn-sm btn-outline-primary">
                                        <i class="ti ti-download me-1"></i> Lihat Bukti
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Order Timeline --}}
        <div class="card c-card">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="ti ti-timeline me-2"></i>Timeline Pesanan</h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    @php
                        $timeline = [
                            'order_placed' => [
                                'title' => 'Pesanan Dibuat',
                                'icon' => 'ti ti-shopping-cart',
                                'date' => $orderData->created_at,
                                'status' => 'completed',
                            ],
                            'payment_confirmed' => [
                                'title' => 'Pembayaran Dikonfirmasi',
                                'icon' => 'ti ti-credit-card',
                                'date' => $orderData->payment_confirmed_at,
                                'status' => $orderData->payment_status === 'paid' ? 'completed' : 'pending',
                            ],
                            'processing' => [
                                'title' => 'Sedang Diproses',
                                'icon' => 'ti ti-package',
                                'date' => $orderData->processing_at,
                                'status' => in_array($orderData->status, ['processing', 'shipped', 'delivered'])
                                    ? 'completed'
                                    : 'pending',
                            ],
                            'shipped' => [
                                'title' => 'Dikirim',
                                'icon' => 'ti ti-truck',
                                'date' => $orderData->shipped_at,
                                'status' => in_array($orderData->status, ['shipped', 'delivered'])
                                    ? 'completed'
                                    : 'pending',
                            ],
                            'delivered' => [
                                'title' => 'Diterima',
                                'icon' => 'ti ti-check',
                                'date' => $orderData->delivered_at,
                                'status' => $orderData->status === 'delivered' ? 'completed' : 'pending',
                            ],
                        ];
                    @endphp

                    @foreach ($timeline as $key => $item)
                        <div class="timeline-item d-flex align-items-start mb-3">
                            <div class="timeline-icon me-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center {{ $item['status'] === 'completed' ? 'bg-success' : 'bg-light' }}"
                                    style="width: 40px; height: 40px;">
                                    <i
                                        class="ti {{ $item['icon'] }} {{ $item['status'] === 'completed' ? 'text-white' : 'text-muted' }}"></i>
                                </div>
                            </div>
                            <div class="timeline-content flex-grow-1">
                                <div
                                    class="fw-semibold {{ $item['status'] === 'completed' ? 'text-success' : 'text-muted' }}">
                                    {{ $item['title'] }}
                                </div>
                                <div class="small text-muted">
                                    @if ($item['date'])
                                        {{ \Carbon\Carbon::parse($item['date'])->format('d M Y H:i') }}
                                    @else
                                        Belum terjadi
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@php
    function getStatusBadge($status)
    {
        $statusMap = [
            'pending' => '<span class="badge bg-warning">Menunggu Pembayaran</span>',
            'paid' => '<span class="badge bg-info">Sudah Dibayar</span>',
            'processing' => '<span class="badge bg-primary">Sedang Diproses</span>',
            'shipped' => '<span class="badge bg-success">Dikirim</span>',
            'delivered' => '<span class="badge bg-success">Diterima</span>',
            'cancelled' => '<span class="badge bg-danger">Dibatalkan</span>',
        ];
        return $statusMap[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    function getPaymentStatusBadge($status)
    {
        $statusMap = [
            'pending' => '<span class="badge bg-warning">Menunggu Pembayaran</span>',
            'paid' => '<span class="badge bg-success">Sudah Dibayar</span>',
            'failed' => '<span class="badge bg-danger">Gagal</span>',
            'expired' => '<span class="badge bg-secondary">Kadaluarsa</span>',
        ];
        return $statusMap[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }
@endphp
