@extends('layouts.customer.app')

@section('title', 'Checkout – Sellora')

@section('content')
    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-3">Informasi Pengiriman</h6>
                    <form action="{{ route('customer.checkout.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" value="{{ $customer->name }}" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nomor Telepon</label>
                                <input type="text" class="form-control" value="{{ $customer->phone }}" readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat Pengiriman</label>
                            <textarea class="form-control" rows="3" readonly>{{ $customer->address }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan (Opsional)</label>
                            <textarea name="note" class="form-control @error('note') is-invalid @enderror" rows="3"
                                placeholder="Catatan tambahan untuk pengiriman">{{ old('note') }}</textarea>
                            @error('note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check"></i> Buat Pesanan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-3">Ringkasan Pesanan</h6>
                    @php
                        $subtotal = 0;
                        $totalDiscount = 0;
                        foreach ($products as $product) {
                            $quantity = $cart[$product->id] ?? 0;
                            $originalPrice = $product->selling_price * $quantity;
                            $finalPrice =
                                $product->discount > 0
                                    ? $product->selling_price * $product->discount * $quantity
                                    : $originalPrice;
                            $subtotal += $finalPrice;
                            $totalDiscount += $originalPrice - $finalPrice;
                        }
                    @endphp

                    @foreach ($products as $product)
                        @php
                            $quantity = $cart[$product->id] ?? 0;
                            $finalPrice =
                                $product->discount > 0
                                    ? $product->selling_price * $product->discount
                                    : $product->selling_price;
                        @endphp
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <div class="fw-semibold">{{ $product->product_name }}</div>
                                <small class="text-muted">{{ $quantity }} x Rp
                                    {{ number_format($finalPrice, 0, ',', '.') }}</small>
                            </div>
                            <div class="text-end">
                                <div class="fw-semibold">Rp {{ number_format($finalPrice * $quantity, 0, ',', '.') }}</div>
                                @if ($product->discount > 0)
                                    <small class="text-decoration-line-through text-muted">
                                        Rp {{ number_format($product->selling_price * $quantity, 0, ',', '.') }}
                                    </small>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal ({{ array_sum($cart) }} item)</span>
                        <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                    </div>
                    @if ($totalDiscount > 0)
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>Diskon</span>
                            <span>-Rp {{ number_format($totalDiscount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    <hr>
                    <div class="d-flex justify-content-between fw-semibold">
                        <span>Total</span>
                        <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                    </div>

                    <div class="alert alert-info mt-3">
                        <small>
                            <i class="ti ti-info-circle"></i>
                            Pesanan akan diproses setelah admin mengkonfirmasi
                            dan akan dikirimkan dengan estimasi 1-5 hari setelah dikonfirmasi
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
