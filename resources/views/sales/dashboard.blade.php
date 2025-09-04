@extends('layouts.app')

@section('content')
    <div class="row justify-content-start w-full">
        <div class="col-3">
            <div class="card">
                <div class="card-body p-3 rounded bg-warning d-flex align-items-center gap-3">
                    <i class="ti ti-settings fs-1 text-white" style="font-size: 2rem !important;"></i>
                    <div class="d-flex flex-column">
                        <p class="fw-semibold fs-3 text-white mb-1">Pesanan Dalam Proses</p>
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
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Order Terbaru</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nomor Order</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($data['orderTerbaru'] ?? []) as $i => $order)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $order->invoice_id ?? '-' }}</td>
                                        <td>{{ optional($order->created_at)->format('d M Y H:i') }}</td>
                                        <td>
                                            @switch($order->transaction_status)
                                                @case('process')
                                                    <span
                                                        class="badge bg-primary">Proses</span>
                                                @break

                                                @case('success')
                                                    <span
                                                        class="badge bg-success">Sukses</span>
                                                @break

                                                @default
                                                    <span
                                                        class="badge bg-secondary">Unknown</span>
                                            @endswitch
                                        </td>
                                        <td>Rp. {{ number_format($order->final_total_amount ?? 0, 2, ',', '.') }}</td>
                                    </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Belum ada data order.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection
