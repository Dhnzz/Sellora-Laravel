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

    {{-- Section: Bundling Aktif --}}
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="mb-0">Bundling Hemat</h5>
        <a href="{{ route('customer.catalog') }}" class="small text-decoration-none">Lihat semua</a>
    </div>
    <div class="row g-3">
        @foreach ($bundles as $b)
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card c-card h-100">
                    <img src="{{ $b->flyer ?? 'https://picsum.photos/600/400?random=' . ($loop->index + 11) }}"
                        class="card-img-top" alt="{{ $b->bundle_name }}" style="height:160px;object-fit:cover;">
                    <div class="card-body">
                        <div class="small text-muted mb-1">
                            {{ \Carbon\Carbon::parse($b->end_date)->isPast() ? 'Berakhir' : 'Berlaku' }} s/d
                            {{ \Carbon\Carbon::parse($b->end_date)->format('d M Y') }}</div>
                        <h6 class="card-title mb-1">{{ $b->bundle_name }}</h6>
                        <div class="small">
                            <span class="text-decoration-line-through text-muted">Rp
                                {{ number_format($b->original_price, 0, ',', '.') }}</span>
                            <span class="ms-1 text-success fw-semibold">Rp
                                {{ number_format($b->special_bundle_price, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
        @if (empty($bundles) || count($bundles) === 0)
            <div class="col-12">
                <div class="alert alert-light border">Belum ada bundling aktif saat ini.</div>
            </div>
        @endif
    </div>
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
        });
    </script>
@endpush
