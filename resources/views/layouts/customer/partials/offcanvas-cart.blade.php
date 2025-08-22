<div class="offcanvas offcanvas-end offcanvas-cart" tabindex="-1" id="offcanvasCart" aria-labelledby="offcanvasCartLabel"
    style="width: 420px;">
    <div class="offcanvas-header">
        <h5 id="offcanvasCartLabel" class="mb-0"><i class="ti ti-shopping-cart me-2"></i>Keranjang</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <div id="cartItems" class="list-group list-group-flush mb-3">
            {{-- render items via JS atau include server-side --}}
            {{-- contoh item statis: --}}
            {{-- <div class="list-group-item d-flex align-items-center justify-content-between">
        <div class="me-2">
          <div class="fw-semibold">Aqua 600ml</div>
          <small class="text-muted">Rp 4.000 x 2</small>
        </div>
        <div class="text-end">
          <div class="fw-semibold">Rp 8.000</div>
          <button class="btn btn-link text-danger p-0 small remove-item">Hapus</button>
        </div>
      </div> --}}
        </div>

        <div class="mt-auto">
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Subtotal</span>
                <strong id="cartSubtotal">Rp 0</strong>
            </div>
            <div class="d-grid gap-2">
                <a href="" class="btn btn-outline-secondary">Lihat Keranjang</a>
                <a href="" class="btn btn-primary">Checkout</a>
            </div>
        </div>
    </div>
</div>
