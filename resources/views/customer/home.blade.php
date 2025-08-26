@extends('layouts.customer.app')

@section('content')
    <div class="owl-carousel owl-theme c-hero mb-4" style="max-width: 1114px; max-height: 200px;" id="heroCarousel">
        @foreach ($bundles as $item)
            <div class="item me-2">
                <a href="{{ route('customer.bundle.index', $item->id) }}">
                    @if ($item->flyer != 'uploads/images/product_bundles/bundle-1.png')
                        <img src="{{ asset('storage/' . $item->flyer) }}" alt="Promo {{ $loop->iteration }}">
                    @else
                        <img src="{{ asset($item->flyer) }}" alt="Promo {{ $loop->iteration }}">
                    @endif
                </a>
            </div>
        @endforeach
    </div>

    {{-- Section: Diskon Aktif --}}
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="mb-0">Produk Promo</h5>
        <a href="{{ route('customer.catalog') }}" class="small text-decoration-none">Lihat semua</a>
    </div>
    <div class="owl-carousel owl-theme mb-5" style="max-height: fit-content" id="discountProductsCarousel">
        @foreach ($discountProducts as $item)
            <div class="item">
                <div class="card c-card h-100">
                    @if ($item->image != 'uploads/images/products/product-1.png')
                        <img src="{{ asset('storage/' . $item->image) }}" class="card-img-top" alt="{{ $item->name }}"
                            style="height:160px;object-fit:cover;">
                    @else
                        <img src="{{ asset($item->image) }}" class="card-img-top" alt="{{ $item->name }}"
                            style="height:160px;object-fit:cover;">
                    @endif
                    <div class="card-body">
                        <h6 class="card-title mb-1">{{ $item->name }}</h6>
                        <div class="mb-1">
                            <span class="badge bg-danger me-1">-{{ $item->discount * 100 }}%</span>
                        </div>
                        <div class="small">
                            <span class="text-decoration-line-through text-muted">Rp
                                {{ number_format($item->selling_price, 0, ',', '.') }}</span>
                            <span class="ms-1 text-success fw-semibold">Rp
                                {{ number_format($item->selling_price * $item->discount, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Section: Rekomendasi Untuk Anda --}}
    @if (isset($recommendedProducts) && $recommendedProducts->isNotEmpty())
        <div class="d-flex align-items-center justify-content-between mb-2 mt-4">
            <h5 class="mb-0">Rekomendasi Untuk Anda</h5>
            <a href="{{ route('customer.catalog') }}" class="small text-decoration-none">Lihat semua</a>
        </div>
        <div class="owl-carousel owl-theme mb-5" style="max-height: fit-content" id="recommendedProductsCarousel">
            @foreach ($recommendedProducts as $item)
                <div class="item">
                    <div class="card c-card h-100">
                        @if ($item->image != 'uploads/images/products/product-1.png')
                            <img src="{{ asset('storage/' . $item->image) }}" class="card-img-top"
                                alt="{{ $item->name }}" style="height:160px;object-fit:cover;">
                        @else
                            <img src="{{ asset($item->image) }}" class="card-img-top" alt="{{ $item->name }}"
                                style="height:160px;object-fit:cover;">
                        @endif
                        <div class="card-body">
                            <h6 class="card-title mb-1">{{ $item->name }}</h6>
                            @if ($item->discount > 0)
                                <div class="mb-1">
                                    <span class="badge bg-danger me-1">-{{ $item->discount * 100 }}%</span>
                                </div>
                                <div class="small">
                                    <span class="text-decoration-line-through text-muted">Rp
                                        {{ number_format($item->selling_price, 0, ',', '.') }}</span>
                                    <span class="ms-1 text-success fw-semibold">Rp
                                        {{ number_format($item->selling_price * $item->discount, 0, ',', '.') }}</span>
                                </div>
                            @else
                                <div class="small">
                                    <span class="fw-semibold">Rp
                                        {{ number_format($item->selling_price, 0, ',', '.') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Section: Rekomendasi Pengguna
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="mb-0">Produk Promo</h5>
        <a href="{{ route('customer.catalog') }}" class="small text-decoration-none">Lihat semua</a>
    </div>
    <div class="owl-carousel owl-theme" id="discountProductsCarousel">
        @foreach ($discountProducts as $item)
            <div class="item">
                <div class="card c-card h-100">
                    @if ($item->image != 'uploads/images/products/product-1.png')
                        <img src="{{ asset('storage/' . $item->image) }}" class="card-img-top" alt="{{ $item->name }}"
                            style="height:160px;object-fit:cover;">
                    @else
                        <img src="{{ asset($item->image) }}" class="card-img-top" alt="{{ $item->name }}"
                            style="height:160px;object-fit:cover;">
                    @endif
                    <div class="card-body">
                        <h6 class="card-title mb-1">{{ $item->name }}</h6>
                        <div class="mb-1">
                            <span class="badge bg-danger me-1">-{{ $item->discount * 100 }}%</span>
                        </div>
                        <div class="small">
                            <span class="text-decoration-line-through text-muted">Rp
                                {{ number_format($item->selling_price, 0, ',', '.') }}</span>
                            <span class="ms-1 text-success fw-semibold">Rp
                                {{ number_format($item->selling_price * $item->discount, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div> --}}
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#heroCarousel').owlCarousel({
                loop: true,
                margin: 10,
                nav: true,
                dots: false,
                autoplay: true,
                autoplayTimeout: 5000,
                autoplayHoverPause: true,
                responsive: {
                    0: {
                        items: 1
                    },
                    600: {
                        items: 1
                    },
                    1000: {
                        items: 1
                    }
                }
            });

            $('#discountProductsCarousel').owlCarousel({
                loop: true,
                margin: 12,
                nav: false,
                dots: false,
                autoplay: false,
                responsive: {
                    0: {
                        items: 2
                    },
                    600: {
                        items: 3
                    },
                    1000: {
                        items: 4
                    }
                }
            });

            $('#recommendedProductsCarousel').owlCarousel({
                loop: true,
                margin: 12,
                nav: false,
                dots: false,
                autoplay: false,
                responsive: {
                    0: {
                        items: 2
                    },
                    600: {
                        items: 3
                    },
                    1000: {
                        items: 4
                    }
                }
            });
        });
    </script>
@endpush
