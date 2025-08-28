@extends('layouts.customer.app')

@section('title', 'Katalog – Sellora')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="sticky-top">

                        <h6 class="mb-3">Filter</h6>
                        <form method="GET" action="{{ route('customer.catalog') }}" class="mb-5">
                            <div>
                                <label class="form-label small text-muted">Brand</label>
                                <select name="brand" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="all" {{ ($selectedBrand ?? 'all') == 'all' ? 'selected' : '' }}>Semua
                                    </option>
                                    @foreach ($brands as $b)
                                        <option value="{{ $b->name }}"
                                            {{ (string) ($selectedBrand ?? '') === (string) $b->name ? 'selected' : '' }}>
                                            {{ $b->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                        <h6 class="mb-3">Urutkan</h6>
                        <div class="mb-2">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-1">
                                    <a href="{{ request()->fullUrlWithQuery(['sortBy' => 'recommended']) }}"
                                        class="text-decoration-none {{ request('sortBy') == 'recommended' ? 'fw-bold text-primary' : 'text-secondary' }}">
                                        Rekomendasi
                                    </a>
                                </li>
                                <li class="mb-1">
                                    <a href="{{ request()->fullUrlWithQuery(['sortBy' => 'newest']) }}"
                                        class="text-decoration-none {{ request('sortBy') == 'newest' ? 'fw-bold text-primary' : 'text-secondary' }}">
                                        Terbaru
                                    </a>
                                </li>
                                <li class="mb-1">
                                    <a href="{{ request()->fullUrlWithQuery(['sortBy' => 'lowestPrice']) }}"
                                        class="text-decoration-none {{ request('sortBy') == 'lowestPrice' ? 'fw-bold text-primary' : 'text-secondary' }}">
                                        Harga ↑
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ request()->fullUrlWithQuery(['sortBy' => 'highestPrice']) }}"
                                        class="text-decoration-none {{ request('sortBy') == 'highestPrice' ? 'fw-bold text-primary' : 'text-secondary' }}">
                                        Harga ↓
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-9">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h5 class="mb-0">Katalog Produk</h5>
            </div>

            @if ($products->count() === 0)
                <div class="alert alert-light border">Produk belum tersedia.</div>
            @endif

            <div class="row g-3">
                @foreach ($products as $p)
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="card c-card h-100">
                            @php
                                $img = $p->image;
                                $useStorage = $img && $img !== 'uploads/images/products/product-1.png';
                            @endphp
                            <div class="ratio ratio-4x3 bg-light">
                                @if ($img)
                                    <img src="{{ $useStorage ? asset('storage/' . $img) : asset($img) }}"
                                        class="w-100 h-100" style="object-fit: cover;" alt="{{ $p->product_name }}">
                                @else
                                    <div class="d-flex justify-content-center align-items-center text-muted">No Image</div>
                                @endif
                            </div>
                            <div class="card-body">
                                <div class="small text-muted mb-1">{{ $p->brand_name }}</div>
                                <h6 class="card-title mb-1">{{ $p->product_name }}</h6>
                                @if ($p->discount > 0)
                                    <div class="small">
                                        <span class="text-decoration-line-through text-muted">Rp
                                            {{ number_format($p->selling_price, 0, ',', '.') }}</span>
                                        <span class="ms-1 text-success fw-semibold">Rp
                                            {{ number_format($p->selling_price * $p->discount, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="mb-1">
                                        <span class="badge bg-danger">Diskon {{ $p->discount * 100 }}%</span>
                                    </div>
                                @else
                                    <div class="small fw-semibold">Rp {{ number_format($p->selling_price, 0, ',', '.') }}
                                    </div>
                                @endif
                            </div>
                            <div class="card-footer bg-white">
                                <div class="d-grid">
                                    <button class="btn btn-primary btn-sm add-to-cart" data-id="{{ $p->id }}"
                                        data-name="{{ $p->product_name }}">+
                                        Keranjang</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-3">
                {{ $products->links() }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            $(document).on('click', '.add-to-cart', function() {
                const id = $(this).data('id');
                const btn = $(this);

                btn.prop('disabled', true).text('Menambahkan...');

                $.ajax({
                    url: '{{ route('customer.cart.add') }}',
                    method: 'POST',
                    data: {
                        product_id: id,
                        quantity: 1,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            // Update cart count di navbar jika ada
                            $('.cart-count').text(response.cart_count);
                        } else {
                            toastr.error('Gagal menambahkan ke keranjang');
                        }
                    },
                    error: function() {
                        toastr.error('Terjadi kesalahan');
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('+ Keranjang');
                    }
                });
            });
        });
    </script>
@endpush
