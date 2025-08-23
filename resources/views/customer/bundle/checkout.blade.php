@extends('layouts.customer.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                @if ($bundle->flyer != 'uploads/images/product_bundles/bundle-1.png')
                    <img class="card-img-top" src="{{ asset('storage/' . $bundle->flyer) }}" alt="Title" />
                @else
                    <img class="card-img-top" src="{{ asset($bundle->flyer) }}" alt="Title" />
                @endif
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h6>Syarat & Ketentuan Bundling</h6>
                        <ul>
                            <li>Bundling hanya berlaku selama periode promo yang tertera.</li>
                            <li>Transaksi bundle hanya dilakukan sendiri diluar transaksi lainnya.</li>
                            <li>Harga spesial bundling tidak dapat digabung dengan promo lain.</li>
                            <li>Produk dalam bundling tidak dapat ditukar atau dikembalikan secara parsial.</li>
                            <li>Pemesanan bundling hanya dapat dilakukan melalui website ini.</li>
                            <li>Jika terjadi pembatalan, pengembalian dana mengikuti kebijakan toko.</li>
                        </ul>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <h4 class="card-title mb-1">{{ $bundle->bundle_name }}</h4>
                            <div class="mb-2 text-muted">
                                Periode:
                                {{ \Carbon\Carbon::parse($bundle->start_date)->format('d M Y') }} -
                                {{ \Carbon\Carbon::parse($bundle->end_date)->format('d M Y') }}
                            </div>
                            <p class="card-text">{{ $bundle->description }}</p>
                            <h6 class="mt-4 mb-2">Daftar Produk dalam Bundle</h6>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Nama Produk</th>
                                            <th>Qty</th>
                                            <th>Harga Satuan</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $total = 0;
                                        @endphp
                                        @foreach ($bundle->product_bundle_items as $i => $item)
                                            @php
                                                $subtotal = $item->quantity * $item->product->selling_price;
                                                $total += $subtotal;
                                            @endphp
                                            <tr>
                                                <td>{{ $i + 1 }}</td>
                                                <td>{{ $item->product->name }}</td>
                                                <td>{{ $item->quantity }}</td>
                                                <td>Rp {{ number_format($item->product->selling_price, 0, ',', '.') }}</td>
                                                <td>Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 bg-light">
                                <h5 class="mb-3">Ringkasan Pembelian</h5>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Total Harga Asli</span>
                                    <span class="text-decoration-line-through text-danger">Rp
                                        {{ number_format($total, 0, ',', '.') }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Harga Spesial Bundle</span>
                                    <span class="fw-bold text-success fs-5">Rp
                                        {{ number_format($bundle->special_bundle_price, 0, ',', '.') }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Hemat</span>
                                    <span class="fw-semibold text-primary">Rp
                                        {{ number_format($total - $bundle->special_bundle_price, 0, ',', '.') }}</span>
                                </div>
                                <hr>
                                <form action="" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-success w-100">Checkout Sekarang</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
