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
    <div class="card">

        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Detail Bundle</h5>
                <a href="{{ route('owner.bundle.index') }}" class="btn btn-sm btn-primary">Kembali</a>
            </div>
            <div class="row mb-3">
                <div class="col-12 text-center mb-3">
                    @if ($data['bundle']->flyer != 'uploads/images/product_bundles/bundle-1.png')
                        <img src="{{ asset('storage/' . $data['bundle']->flyer) }}" alt="Flyer Bundle"
                            class="img-fluid rounded" style="max-height: 200px; object-fit: contain;">
                    @else
                        <img src="{{ asset('uploads/images/product_bundles/bundle-1.png') }}" alt="Flyer Bundle"
                            class="img-fluid rounded" style="max-height: 200px; object-fit: contain;">
                    @endif
                </div>
                <div class="col-md-6 mb-2">
                    <div class="mb-2">
                        <span class="fw-semibold text-muted">Nama Bundle</span><br>
                        <span class="fs-5">{{ $data['bundle']->bundle_name }}</span>
                    </div>
                    <div class="mb-2">
                        <span class="fw-semibold text-muted">Deskripsi</span><br>
                        <span>{{ $data['bundle']->description ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="fw-semibold text-muted">Periode</span><br>
                        <span>
                            {{ \Carbon\Carbon::parse($data['bundle']->start_date)->format('d M Y') }}
                            &ndash;
                            {{ \Carbon\Carbon::parse($data['bundle']->end_date)->format('d M Y') }}
                        </span>
                    </div>
                </div>
                <div class="col-md-6 mb-2">
                    <div class="mb-2">
                        <span class="fw-semibold text-muted">Harga Asli</span><br>
                        <span class="fs-6 text-decoration-line-through text-danger">Rp
                            {{ number_format($data['bundle']->original_price, 0, ',', '.') }}</span>
                    </div>
                    <div class="mb-2">
                        <span class="fw-semibold text-muted">Harga Spesial</span><br>
                        <span class="fs-5 text-success">Rp
                            {{ number_format($data['bundle']->special_bundle_price, 0, ',', '.') }}</span>
                    </div>
                    <div>
                        <span class="fw-semibold text-muted">Status</span><br>
                        {!! $data['bundle']->is_active
                            ? '<span class="badge bg-success px-3 py-2">Aktif</span>'
                            : '<span class="badge bg-secondary px-3 py-2">Nonaktif</span>' !!}
                    </div>
                </div>
            </div>

            <h6 class="mb-2">Item Produk</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Produk</th>
                            <th>Harga</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $total = 0;
                        @endphp
                        @foreach ($data['bundle']->product_bundle_items as $i => $it)
                            @php
                                $subtotal = ($it->product->selling_price ?? 0) * $it->quantity;
                                $total += $subtotal;
                            @endphp
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $it->product->name ?? '-' }}</td>
                                <td>Rp {{ number_format($it->product->selling_price ?? 0, 0, ',', '.') }}</td>
                                <td>{{ $it->quantity }}</td>
                                <td>Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="4" class="text-end fw-bold">Total</td>
                            <td class="fw-bold">Rp {{ number_format($total, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
@endsection
