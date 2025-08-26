@extends('layouts.customer.app')

@section('title', 'Keranjang Belanja – Sellora')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="mb-0">Keranjang Belanja</h5>
        @if ($products->isNotEmpty())
            <div class="d-flex gap-2">
                <button class="btn btn-outline-danger btn-sm" id="clear-cart">
                    <i class="ti ti-trash"></i> Kosongkan
                </button>
                <a href="{{ route('customer.checkout.index') }}" class="btn btn-primary btn-sm">
                    <i class="ti ti-shopping-cart"></i> Checkout
                </a>
            </div>
        @endif
    </div>

    @if ($products->isEmpty())
        <div class="text-center py-5">
            <i class="ti ti-shopping-cart" style="font-size: 4rem; color: #ccc;"></i>
            <h6 class="mt-3 text-muted">Keranjang belanja kosong</h6>
            <p class="text-muted">Belum ada produk yang ditambahkan ke keranjang</p>
            <a href="{{ route('customer.catalog') }}" class="btn btn-primary">
                Mulai Belanja
            </a>
        </div>
    @else
        <div class="row">
            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-body">
                        @foreach ($products as $product)
                            @php
                                $quantity = $cart[$product->id] ?? 0;
                                $img = $product->image;
                                $useStorage = $img && $img !== 'uploads/images/products/product-1.png';
                                $finalPrice =
                                    $product->discount > 0
                                        ? $product->selling_price * $product->discount
                                        : $product->selling_price;
                            @endphp
                            <div class="row align-items-center py-3 border-bottom cart-item"
                                data-product-id="{{ $product->id }}">
                                <div class="col-3 col-md-2">
                                    <div class="ratio ratio-1x1 bg-light rounded">
                                        @if ($img)
                                            <img src="{{ $useStorage ? asset('storage/' . $img) : asset($img) }}"
                                                class="w-100 h-100" style="object-fit: cover;"
                                                alt="{{ $product->product_name }}">
                                        @else
                                            <div class="d-flex justify-content-center align-items-center text-muted">No
                                                Image</div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <h6 class="mb-1">{{ $product->product_name }}</h6>
                                    <small class="text-muted">{{ $product->brand_name }}</small>
                                    @if ($product->discount > 0)
                                        <div class="mt-1">
                                            <span class="badge bg-danger">-{{ $product->discount * 100 }}%</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="col-3 col-md-2">
                                    <div class="input-group input-group-sm">
                                        <button class="btn btn-outline-secondary btn-decrease" type="button">-</button>
                                        <input type="number" class="form-control text-center quantity-input"
                                            value="{{ $quantity }}" min="0" max="100"
                                            data-product-id="{{ $product->id }}">
                                        <button class="btn btn-outline-secondary btn-increase" type="button">+</button>
                                    </div>
                                </div>
                                <div class="col-6 col-md-2 text-end">
                                    <div class="fw-semibold">Rp {{ number_format($finalPrice * $quantity, 0, ',', '.') }}
                                    </div>
                                    @if ($product->discount > 0)
                                        <small class="text-decoration-line-through text-muted">
                                            Rp {{ number_format($product->selling_price * $quantity, 0, ',', '.') }}
                                        </small>
                                    @endif
                                </div>
                                <div class="col-6 col-md-2 text-end">
                                    <button class="btn btn-outline-danger btn-sm remove-item">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3">Ringkasan Belanja</h6>
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
                        <div class="d-grid mt-3">
                            <a href="{{ route('customer.checkout.index') }}" class="btn btn-primary">
                                Lanjut ke Pembayaran
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        $(function() {
            // Update quantity
            $('.quantity-input').on('change', function() {
                const productId = $(this).data('product-id');
                const quantity = parseInt($(this).val());
                updateCartItem(productId, quantity);
            });

            // Increase quantity
            $('.btn-increase').on('click', function() {
                const input = $(this).siblings('.quantity-input');
                const currentVal = parseInt(input.val());
                input.val(currentVal + 1).trigger('change');
            });

            // Decrease quantity
            $('.btn-decrease').on('click', function() {
                const input = $(this).siblings('.quantity-input');
                const currentVal = parseInt(input.val());
                if (currentVal > 0) {
                    input.val(currentVal - 1).trigger('change');
                }
            });

            // Remove item
            $('.remove-item').on('click', function() {
                const productId = $(this).closest('.cart-item').data('product-id');
                removeCartItem(productId);
            });

            // Clear cart
            $('#clear-cart').on('click', function() {
                if (confirm('Yakin ingin mengosongkan keranjang?')) {
                    clearCart();
                }
            });

            function updateCartItem(productId, quantity) {
                $.ajax({
                    url: '{{ route('customer.cart.update') }}',
                    method: 'PUT',
                    data: {
                        product_id: productId,
                        quantity: quantity,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            $('.cart-count').text(response.cart_count);
                            if (quantity === 0) {
                                location.reload();
                            } else {
                                location.reload(); // Reload untuk update total
                            }
                        }
                    },
                    error: function() {
                        toastr.error('Gagal memperbarui keranjang');
                    }
                });
            }

            function removeCartItem(productId) {
                $.ajax({
                    url: '{{ route('customer.cart.remove') }}',
                    method: 'DELETE',
                    data: {
                        product_id: productId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            $('.cart-count').text(response.cart_count);
                            location.reload();
                        }
                    },
                    error: function() {
                        toastr.error('Gagal menghapus item');
                    }
                });
            }

            function clearCart() {
                $.ajax({
                    url: '{{ route('customer.cart.clear') }}',
                    method: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            $('.cart-count').text('0');
                            location.reload();
                        }
                    },
                    error: function() {
                        toastr.error('Gagal mengosongkan keranjang');
                    }
                });
            }
        });
    </script>
@endpush
