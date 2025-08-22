<nav class="navbar navbar-expand-lg fixed-top" id="navbar" style="height: fit-content">
    <div class="container">
        <a class="navbar-brand" href="{{ url('/') }}">
            <img src="{{ asset('assets/front/img/logo.jpg') }}" alt="Logo" />
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown"
            aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars-staggered"></i>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <ul class="navbar-nav m-auto gap-0 gap-lg-2">
                <li class="nav-item">
                    <a class="nav-link" href="#about-us">About Us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#information">Information</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#contact-us">Contact Us</a>
                </li>
            </ul>

            <div>
                <a class="btn btn-nav-link" href="">
                    <i class="fa-solid fa-table-list"></i>&nbsp;
                    Catalog
                </a>
                <a class="btn btn-nav-link position-relative" href="">
                    <i class="fa-solid fa-cart-shopping"></i>&nbsp;
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark"
                        style="font-size: 0.8em;">
                        {{ $cartCount ?? 0 }}
                        <span class="visually-hidden">jumlah item di keranjang</span>
                    </span>
                </a>
                @auth
                    <form id="logout-form-front-header" action="{{ route('logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-nav-link">
                            <i class="fa-solid fa-right-from-bracket"></i>&nbsp;
                            Logout  
                        </button>
                    </form>
                @endauth
            </div>
        </div>
    </div>
</nav>
