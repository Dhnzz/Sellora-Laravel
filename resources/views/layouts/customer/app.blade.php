<!doctype html>
<html lang="id" data-bs-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name') . ' – Belanja Hemat')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Bootstrap & Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">

    {{-- Toastr (opsional notifikasi) --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />

    {{-- Custom CSS Customer --}}
    <style>
        :root {
            --c-primary: #2563eb;
            /* biru elegan */
            --c-dark: #111827;
            --c-muted: #6b7280;
            --c-bg: #f8fafc;
            --radius: 18px;
        }

        body {
            background: var(--c-bg);
        }

        .c-header {
            position: sticky;
            top: 0;
            z-index: 1030;
            background: #fff;
            border-bottom: 1px solid #eef2f7;
        }

        .c-header .brand {
            font-weight: 800;
            letter-spacing: .2px;
            color: var(--c-dark);
        }

        .c-search input {
            border-radius: 999px;
            padding-left: 42px;
        }

        .c-search .ti {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--c-muted);
        }

        .c-catbar {
            background: #fff;
            border-bottom: 1px solid #eef2f7;
        }

        .c-hero {
            border-radius: var(--radius);
            overflow: hidden;
        }

        .c-card {
            border: none;
            border-radius: var(--radius);
            box-shadow: 0 6px 18px rgba(17, 24, 39, .06);
        }

        .c-card .price {
            font-weight: 700;
            color: var(--c-dark);
        }

        .c-chip {
            background: #eef2ff;
            color: #3730a3;
            padding: .25rem .6rem;
            border-radius: 999px;
            font-size: .75rem;
        }

        .c-footer {
            background: #0f172a;
            color: #cbd5e1;
        }

        .c-footer a {
            color: #e2e8f0;
            text-decoration: none;
        }

        .c-footer a:hover {
            text-decoration: underline;
        }

        .badge-cart {
            position: absolute;
            top: -6px;
            right: -6px;
            background: #ef4444;
        }

        .offcanvas-cart .list-group-item {
            border: none;
            border-bottom: 1px solid #f1f5f9;
        }
    </style>

    @stack('head')
    @yield('head')
</head>

<body>

    {{-- HEADER --}}
    <header class="c-header">
        <div class="container py-2">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('customer.home') }}"
                    class="brand h4 mb-0 text-decoration-none d-flex align-items-center gap-2">
                    <i class="ti ti-truck" style="font-size: 3rem"></i> {{ config('app.name', 'Sellora') }}
                </a>

                {{-- Search --}}
                <form action="{{ route('customer.catalog') }}" class="ms-auto flex-grow-1 c-search position-relative"
                    style="max-width:680px;">
                    <i class="ti ti-search"></i>
                    <input class="form-control form-control-lg" type="search" name="q"
                        value="{{ request('q') }}" placeholder="Cari produk, merek, atau kategori...">
                </form>

                {{-- Actions --}}
                <div class="d-flex align-items-center gap-2 ms-2">
                    <a href="{{ route('customer.catalog') }}" class="btn btn-outline-secondary d-none d-md-inline-flex">
                        <i class="ti ti-grid-dots me-1"></i> Katalog
                    </a>

                    {{-- Cart Button (offcanvas) --}}
                    <button class="btn btn-primary position-relative" data-bs-toggle="offcanvas"
                        data-bs-target="#offcanvasCart">
                        <i class="ti ti-shopping-cart"></i>
                        <span id="cartBadge" class="badge badge-cart rounded-pill">0</span>
                    </button>

                    {{-- Account dropdown --}}
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="ti ti-user"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href=""><i
                                        class="ti ti-receipt me-2"></i>Pesanan Saya</a></li>
                            <li><a class="dropdown-item" href="#"><i class="ti ti-heart me-2"></i>Wishlist</a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">@csrf
                                    <button class="dropdown-item text-danger"><i
                                            class="ti ti-logout me-2"></i>Keluar</button>
                                </form>
                            </li>
                        </ul>
                    </div>

                </div>
            </div>
        </div>

        {{-- Category Bar (opsional) --}}
        <div class="c-catbar d-none d-md-block">
            <div class="container py-2">
                <div class="d-flex gap-3 small">
                    <a href="{{ route('customer.catalog') }}" class="text-decoration-none text-dark">Semua</a>
                    {{-- contoh kategori statis; nanti ganti dinamis --}}
                    <a href="{{ route('customer.catalog', ['cat' => 'minuman']) }}"
                        class="text-decoration-none text-dark">Minuman</a>
                    <a href="{{ route('customer.catalog', ['cat' => 'makanan']) }}"
                        class="text-decoration-none text-dark">Makanan</a>
                    <a href="{{ route('customer.catalog', ['cat' => 'perawatan']) }}"
                        class="text-decoration-none text-dark">Perawatan</a>
                    <span class="ms-auto c-chip">Gratis ongkir s&k berlaku</span>
                </div>
            </div>
        </div>
    </header>

    {{-- MINI CART OFFCANVAS --}}
    @include('layouts.customer.partials.offcanvas-cart')

    <main class="container my-4">
        @yield('content')
    </main>

    {{-- FOOTER --}}
    <footer class="c-footer mt-5 pt-5 pb-4">
        <div class="container">
            <div class="row g-4">
                <div class="col-12 col-md-4">
                    <h5 class="mb-2">{{ config('app.name', 'Sellora') }}</h5>
                    <p class="text-secondary">Belanja kebutuhan toko makin gampang. Harga bersaing, kirim cepat, dan ada
                        bundling hemat!</p>
                </div>
                <div class="col-6 col-md-2">
                    <h6>Layanan</h6>
                    <ul class="list-unstyled small">
                        <li><a href="#">Bantuan</a></li>
                        <li><a href="#">Kebijakan</a></li>
                        <li><a href="#">Syarat & Ketentuan</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-3">
                    <h6>Ikuti Kami</h6>
                    <div class="d-flex gap-3">
                        <a href="#"><i class="ti ti-brand-instagram"></i></a>
                        <a href="#"><i class="ti ti-brand-facebook"></i></a>
                        <a href="#"><i class="ti ti-brand-x"></i></a>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <h6>Butuh bantuan?</h6>
                    <div class="small">support@sellora.test</div>
                    <div class="small">+62 812‑XXXX‑XXXX</div>
                </div>
            </div>
            <hr class="border-secondary-subtle my-4">
            <div class="small text-secondary">&copy; {{ date('Y') }} {{ config('app.name', 'Sellora') }}. All
                rights reserved.</div>
        </div>
    </footer>

    {{-- Scripts --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    {{-- Cart badge sample loader (ganti ke API cart kamu) --}}
    {{-- <script>
        // contoh: ambil jumlah item cart dari endpoint kamu
        // $.get("{{ route('shop.cart.count') }}").done(res => $('#cartBadge').text(res.count || 0));
    </script> --}}

    @stack('scripts')
    @yield('scripts')
</body>

</html>
