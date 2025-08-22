@extends('layouts.customer.app')

@section('title', 'Beranda – Sellora')

@section('content')
    {{-- HERO / Carousel Bundling (pakai komponen yang sudah kamu punya, contoh sederhana) --}}
    <div id="heroCarousel" class="carousel slide c-hero mb-4" data-bs-ride="carousel">
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="https://picsum.photos/1200/360?random=1" class="d-block w-100" alt="Promo 1">
            </div>
            <div class="carousel-item">
                <img src="https://picsum.photos/1200/360?random=2" class="d-block w-100" alt="Promo 2">
            </div>
            <div class="carousel-item">
                <img src="https://picsum.photos/1200/360?random=3" class="d-block w-100" alt="Promo 3">
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Sebelumnya</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Selanjutnya</span>
        </button>
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
