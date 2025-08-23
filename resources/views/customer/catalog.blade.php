@extends('layouts.customer.app')

@section('title', 'Katalog – Sellora')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="mb-0">Katalog Produk</h5>
        <div class="d-none d-md-flex align-items-center gap-2">
            <span class="small text-muted">Urutkan:</span>
            {{-- ini cuma contoh; urutan aslinya sudah kamu atur di controller (personalized) --}}
            <div class="btn-group btn-group-sm" role="group">
                <a href="{{ request()->fullUrlWithQuery(['sort' => 'reco']) }}"
                    class="btn btn-outline-secondary {{ request('sort') == 'reco' ? 'active' : '' }}">Rekomendasi</a>
                <a href="{{ request()->fullUrlWithQuery(['sort' => 'new']) }}"
                    class="btn btn-outline-secondary {{ request('sort') == 'new' ? 'active' : '' }}">Terbaru</a>
                <a href="{{ request()->fullUrlWithQuery(['sort' => 'price_asc']) }}"
                    class="btn btn-outline-secondary {{ request('sort') == 'price_asc' ? 'active' : '' }}">Harga ↑</a>
                <a href="{{ request()->fullUrlWithQuery(['sort' => 'price_desc']) }}"
                    class="btn btn-outline-secondary {{ request('sort') == 'price_desc' ? 'active' : '' }}">Harga ↓</a>
            </div>
        </div>
    </div>

    @if ($products->count() === 0)
        <div class="alert alert-light border">Produk belum tersedia.</div>
    @endif

    <div class="row g-3">
        @foreach ($products as $p)
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card c-card h-100">
                    <div class="ratio ratio-4x3 bg-light">
                        <div class="d-flex justify-content-center align-items-center text-muted">No Image</div>
                    </div>
                    <div class="card-body">
                        <div class="small text-muted mb-1">#{{ $p->id }} ·
                            {{ $p->created_at?->diffForHumans(null, true) . ' lalu' }}</div>
                        <h6 class="card-title mb-1">{{ $p->name }}</h6>
                        <div class="price">Rp {{ number_format($p->selling_price, 0, ',', '.') }}</div>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="d-grid">
                            <button class="btn btn-primary btn-sm add-to-cart" data-id="{{ $p->id }}">+
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
@endsection

@push('scripts')
    <script>
        $(function() {
            $(document).on('click', '.add-to-cart', function() {
                const id = $(this).data('id');
                // TODO: ganti ke endpoint cart kamu
                // $.post("{{ route('shop.cart.store') }}", { product_id:id, qty:1, _token:"{{ csrf_token() }}" })
                //   .done((res)=>{ $('#cartBadge').text(res.count || 1); toastr.success('Ditambahkan ke keranjang'); })
                //   .fail(()=> toastr.error('Gagal menambahkan'));
                toastr.info('Simulasi tambah produk ' + id + ' ke keranjang');
            });
        });
    </script>
@endpush
