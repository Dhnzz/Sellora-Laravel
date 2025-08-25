@extends('layouts.app')

@section('content')
    <div class="card bg-light-info shadow-none position-relative overflow-hidden">
        <div class="card-body px-4 py-3">
            <h4 class="fw-semibold mb-8">{{ $data['title'] ?? '' }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    @foreach ($data['breadcrumbs'] as $item)
                        @if ($loop->last)
                            <li class="breadcrumb-item active" aria-current="page">{{ $item['name'] }}</li>
                        @else
                            <li class="breadcrumb-item">
                                <a href="{{ $item['link'] }}" class="text-muted">{{ $item['name'] }}</a>
                            </li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        </div>
    </div>
    <div class="row">
        {{-- STEP 1: PILIH PRODUK --}}
        <div class="col-lg-6 col-12">
            <div class="card h-100">
                <div class="card-body d-flex flex-column" style="height: 400px;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0">Daftar Produk (Dinamis)</h5>
                        <a href="{{ route('owner.bundle.index') }}" class="btn btn-sm btn-danger ms-4">Kembali</a>
                    </div>
                    <div class="flex-grow-1 overflow-auto" style="min-height:0;">
                        <ul class="list-group" id="productList">
                            {{-- diisi via JS (related-rank: global popularity / sering dibeli bareng) --}}
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- KERANJANG ITEM TERPILIH --}}
        <div class="col-lg-6 col-12" id="itemBundleSelectedCard">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-3">Item Bundle Terpilih</h5>
                        <button type="button" class="btn btn-primary btn-sm" id="btnNext" disabled>Lanjut</button>
                    </div>
                    <div id="selectedItems" class="row g-2"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- STEP 2: DETAIL BUNDLE (disembunyikan dulu) --}}
    <div class="row mt-4 d-none" id="detailStep">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Detail Bundle</h5>

                    <form action="{{ route('owner.bundle.store') }}" method="POST" id="bundleForm"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="d-flex flex-column justify-content-center">
                                    <label for="fileInput" class="text-center mx-auto" >
                                        <div class="image-upload-container">
                                            {{-- Gambar yang akan menjadi tombol dan preview --}}
                                            <img src="{{ asset('uploads/images/product_bundles/bundle-1.png') }}"
                                                alt="Gambar Produk" id="productImagePreview"
                                                class="img-fluid rounded-3 mb-2"
                                                style="width: 1114px; height: 200px; object-fit: cover; object-position: center; cursor: pointer;">
                                            {{-- Input file yang disembunyikan --}}
                                            <input type="file" name="flyer" id="fileInput" accept="image/*"
                                                class="d-none @error('flyer') is-invalid @enderror">
                                        </div>
                                    </label>
                                    <span class="control-label mb-2 text-center">Flyer Bundle</span>
                                    @error('flyer')
                                        <small class="invalid-feedback">
                                            {{ $message }}
                                        </small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Bundle</label>
                                <input type="text" class="form-control" name="bundle_name">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Deskripsi Bundle</label>
                                <input type="text" class="form-control" name="description" placeholder="Opsional">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tanggal Mulai</label>
                                <input type="date" class="form-control" name="start_date">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tanggal Berakhir</label>
                                <input type="date" class="form-control" name="end_date">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Harga Spesial</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control" id="specialPriceDisplay" placeholder="0"
                                        inputmode="numeric" autocomplete="off">
                                </div>
                                <input type="hidden" name="special_bundle_price" id="specialPrice"> {{-- angka murni ke DB --}}
                                <small class="d-block mt-1" id="discountInfo"></small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Harga Asli</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control" id="originalPriceDisplay" placeholder="0"
                                        disabled>
                                </div>
                                <input type="hidden" name="original_price" id="originalPriceValue">
                                {{-- angka murni (optional) --}}
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-success">Simpan Bundle</button>
                                <button type="button" class="btn btn-primary ms-2"
                                    id="btnBackToDetailSelect">Kembali</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- 1) HELPER FORMAT RUPIAH + BundleForm (HARUS DI ATAS) --}}
    <script>
        (function() {
            const fmt = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            });

            const $spDisp = $('#specialPriceDisplay');
            const $spVal = $('#specialPrice'); // hidden numeric
            const $opDisp = $('#originalPriceDisplay');
            const $opVal = $('#originalPriceValue'); // hidden numeric
            const $disc = $('#discountInfo');

            function parseCurrency(str) {
                if (!str) return 0;
                let s = String(str).replace(/[^\d,.,-]/g, '');
                if (s.includes(',') && s.includes('.')) s = s.replace(/\./g, '').replace(',', '.');
                else if (s.includes(',')) s = s.replace(',', '.');
                else s = s.replace(/\./g, '');
                const n = parseFloat(s);
                return isNaN(n) ? 0 : n;
            }

            function rp(n) {
                return fmt.format(Number(n || 0));
            }

            function updateDiscountLabel() {
                const original = parseFloat($opVal.val() || 0);
                const special = parseFloat($spVal.val() || 0);
                if (!original || !special) {
                    $disc.text('').removeClass('text-success text-danger');
                    return;
                }
                const diffPct = ((original - special) / original) * 100;
                const sign = diffPct >= 0 ? 'lebih murah' : 'lebih mahal';
                const cls = diffPct >= 0 ? 'text-success' : 'text-danger';
                $disc.removeClass('text-success text-danger').addClass(cls)
                    .text(`${Math.abs(diffPct).toFixed(2)}% ${sign} dari harga asli`);
            }

            // Public API
            window.BundleForm = {
                setOriginalPrice(total) {
                    const n = Number(total || 0);
                    $opVal.val(n);
                    $opDisp.val(rp(n));
                    updateDiscountLabel();
                },
                bindSpecialInput() {
                    $spDisp.on('input', function() {
                        const num = parseCurrency($(this).val());
                        $spVal.val(num);
                        $(this).val(num.toLocaleString('id-ID'));
                        updateDiscountLabel();
                    }).trigger('input');
                }
            };

            // init binder
            window.BundleForm.bindSpecialInput();
        })();
    </script>

    {{-- 2) SCRIPT UTAMA (step pilih → lanjut → submit) --}}
    <script>
        $(function() {

            // Live preview gambar saat file dipilih
            $('#fileInput').on('change', function(event) {
                var file = event.target.files[0];

                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#productImagePreview').attr('src', e.target.result);
                        $('#productImagePreview').css('border-color',
                            'transparent'); // Hapus border saat ada gambar
                    }
                    reader.readAsDataURL(file);
                } else {
                    // Jika tidak ada file dipilih, kembali ke gambar default
                    $('#productImagePreview').attr('src',
                        "{{ asset('uploads/images/product_bundles/bundle-1.png') }}");
                    $('#productImagePreview').css('border-color', '#ccc');
                }
            });

            let selectedItems = {}; // { [id]: {id,name,price,qty} }

            function formatRupiah(n) {
                return Number(n || 0).toLocaleString('id-ID', {
                    minimumFractionDigits: 0
                });
            }

            function renderSelected() {
                const c = $('#selectedItems');
                c.empty();
                Object.values(selectedItems).forEach(it => {
                    c.append(`
                        <div class="col-md-6 mb-3">
                            <div class="border rounded p-3">
                                <div class="d-flex align-items-center justify-content-start mb-1">
                                    <div class="fw-semibold">${it.name}</div>
                                    <div class="d-flex align-items-center gap-1">
                                        <input type="number" class="form-control form-control-sm qty-input text-center" data-id="${it.id}" value="${it.qty}" min="1" maxlength="2" style="width:32px; padding-left:0; padding-right:0;" />
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-item d-flex align-items-center justify-content-center" data-id="${it.id}" style="width:28px; height:28px; padding:0;">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <small class="text-muted">Rp ${formatRupiah(it.price)}</small>
                                </div>
                            </div>
                        </div>
                    `);
                });

                // enable Next kalau ada minimal 1 item
                $('#btnNext').prop('disabled', Object.keys(selectedItems).length === 0);
            }

            function fetchRank() {
                const ids = Object.keys(selectedItems); // selectedItems dari script-mu
                $.get("{{ route('owner.bundle.related-rank') }}", {
                        selected_ids: ids
                    },
                    function(resp) {
                        const list = $('#productList');
                        list.empty();
                        if (resp.products && Array.isArray(resp.products)) {
                            resp.products.forEach(p => {
                                const disabled = selectedItems[p.id] ? 'disabled' : '';
                                list.append(`
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-semibold">${p.name}</div>
                                            <small class="text-muted">Terjual ${p.freq || 0}x</small><br>
                                            <small class="text-muted">Rp ${Number(p.selling_price||0).toLocaleString('id-ID')}</small>
                                        </div>
                                        <button class="btn btn-sm btn-primary add-related"
                                            data-id="${p.id}" data-name="${p.name}" data-price="${p.selling_price}" ${disabled}>
                                            Tambah
                                        </button>
                                    </li>
                                `);
                            });
                        }
                    }
                );
            }

            // init: load popular / related rank awal
            fetchRank();

            // tambah item
            $(document).on('click', '.add-related', function() {
                const id = $(this).data('id');
                if (!selectedItems[id]) {
                    selectedItems[id] = {
                        id,
                        name: $(this).data('name'),
                        price: Number($(this).data('price') || 0),
                        qty: 1
                    };
                    renderSelected();
                    fetchRank();
                }
            });

            // ubah qty
            $(document).on('input', '.qty-input', function() {
                const id = $(this).data('id');
                const v = Math.max(1, Number($(this).val() || 1));
                if (selectedItems[id]) {
                    selectedItems[id].qty = v;
                    renderSelected();
                    refreshOriginalIfDetailVisible();
                }
            });

            // hapus item
            $(document).on('click', '.remove-item', function() {
                const id = $(this).data('id');
                delete selectedItems[id];
                renderSelected();
                fetchRank();
                refreshOriginalIfDetailVisible();
            });

            // helper total harga asli
            function calcTotalOriginal() {
                return Object.values(selectedItems)
                    .reduce((sum, it) => sum + Number(it.price || 0) * Number(it.qty || 0), 0);
            }

            function refreshOriginalIfDetailVisible() {
                if (!$('#detailStep').hasClass('d-none') && window.BundleForm?.setOriginalPrice) {
                    window.BundleForm.setOriginalPrice(calcTotalOriginal());
                }
            }

            // NEXT → hitung harga asli, tampilkan form
            $('#btnNext').on('click', function() {
                const totalOriginal = calcTotalOriginal();
                if (window.BundleForm?.setOriginalPrice) {
                    window.BundleForm.setOriginalPrice(totalOriginal);
                }
                $('#detailStep').removeClass('d-none');
                $('html,body').animate({
                    scrollTop: $('#detailStep').offset().top - 80
                }, 200);
                $('#btnNext').addClass('d-none');
                $('#btnBackToSelect').removeClass('d-none');
                $('#productList').closest('.card').addClass('d-none');
                $('#itemBundleSelectedCard').removeClass('col-lg-6');
                $('#itemBundleSelectedCard').addClass('col-lg-12');
            });

            // BACK dari form ke daftar produk
            $('#btnBackToSelect, #btnBackToDetailSelect').on('click', function() {
                $('#btnNext').removeClass('d-none');
                $('#detailStep').addClass('d-none');
                $('#productList').closest('.card').removeClass('d-none');
                $('#btnBackToSelect').addClass('d-none');
                $('#itemBundleSelectedCard').removeClass('col-lg-12');
                $('#itemBundleSelectedCard').addClass('col-lg-6');
            });

            // SUBMIT: kirim items + detail (JSON)
            $('#bundleForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);

                // 1) Kumpulkan items dari tabel (tanpa JSON.stringify)
                // Ambil items dari selectedItems
                const items = Object.values(selectedItems).map(it => ({
                    product_id: it.id,
                    quantity: it.qty
                }));

                if (items.length === 0) {
                    toastr.error('Pilih minimal 1 produk.');
                    return;
                }

                // 2) Siapkan FormData
                const fd = new FormData();

                // Ambil field biasa dari form (selain file)
                form.serializeArray().forEach(field => {
                    fd.append(field.name, field.value);
                });

                // 3) Append items satu per satu pakai notasi array → TANPA JSON.stringify
                items.forEach((it, i) => {
                    fd.append(`items[${i}][product_id]`, it.product_id);
                    fd.append(`items[${i}][quantity]`, it.quantity);
                });

                // 4) Append file kalau ada
                const fileInput = form.find('input[type="file"][name="flyer"]')[0];
                if (fileInput && fileInput.files.length > 0) {
                    fd.append('flyer', fileInput.files[0]);
                }

                // 5) Kirim AJAX
                $.ajax({
                    url: "{{ route('owner.bundle.store') }}",
                    method: 'POST',
                    data: fd,
                    processData: false, // wajib untuk FormData
                    contentType: false, // wajib untuk FormData
                    dataType: 'json',
                    success: function(res) {
                        // langsung pindah halaman, toastr akan muncul dari flash di index
                        window.location.href = res.redirect;
                    },
                    error: function(xhr) {
                        // Hapus error sebelumnya
                        $('.is-invalid').removeClass('is-invalid');
                        $('.invalid-feedback').remove();

                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            const errors = xhr.responseJSON.errors;

                            // Loop tiap error
                            Object.keys(errors).forEach(function(field) {
                                const input = $(`[name="${field}"]`);
                                if (input.length) {
                                    // Tambah class is-invalid
                                    input.addClass('is-invalid');
                                    // Tambah pesan error di bawah input
                                    input.after(
                                        `<small class="invalid-feedback">${errors[field][0]}</small>`
                                    );
                                }
                            });
                        } else {
                            toastr.error('Gagal menyimpan bundle');
                        }
                    }
                });
            });
        });
    </script>
@endpush
