@extends('layouts.customer.app')

@section('title', 'Keranjang Belanja – Sellora')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="mb-0">Keranjang Belanja</h5>
        <div class="d-flex gap-2" id="cart-actions" style="display: none;">
            <button class="btn btn-outline-danger btn-sm" id="clear-cart">
                <i class="ti ti-trash"></i> Kosongkan
            </button>
            <a href="{{ route('customer.checkout.index') }}" class="btn btn-primary btn-sm">
                <i class="ti ti-shopping-cart"></i> Checkout
            </a>
        </div>
    </div>

    <div id="cart-empty" class="text-center py-5" style="display: none;">
        <i class="ti ti-shopping-cart" style="font-size: 4rem; color: #ccc;"></i>
        <h6 class="mt-3 text-muted">Keranjang belanja kosong</h6>
        <p class="text-muted">Belum ada produk yang ditambahkan ke keranjang</p>
        <a href="{{ route('customer.catalog') }}" class="btn btn-primary">
            Mulai Belanja
        </a>
    </div>

    <div id="cart-content" style="display: none;">
        <div class="row">
            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <div id="cart-items">
                            <!-- Cart items will be rendered here via AJAX -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3">Ringkasan Belanja</h6>
                        <div id="cart-summary">
                            <!-- Cart summary will be rendered here via AJAX -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Define variables for cart functionality
        const cartDataUrl = '{{ route('customer.cart.data') }}';
        const updateCartUrl = '{{ route('customer.cart.update') }}';
        const removeCartUrl = '{{ route('customer.cart.remove') }}';
        const clearCartUrl = '{{ route('customer.cart.clear') }}';
        const checkoutUrl = '{{ route('customer.checkout.index') }}';
        const csrfToken = '{{ csrf_token() }}';
        const storageUrl = '{{ asset('storage') }}';
        const baseUrl = '{{ asset('/') }}';
    </script>
    <script src="{{ asset('assets/js/cart.js') }}"></script>
@endpush
