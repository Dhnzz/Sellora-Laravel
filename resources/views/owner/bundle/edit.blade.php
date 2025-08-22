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
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Edit Bundle</h5>
                <a href="{{ route('owner.bundle.index') }}" class="btn btn-sm btn-primary">Kembali</a>
            </div>

            <form method="POST" action="{{ route('owner.bundle.update', $data['bundle']->id) }}" id="editBundleForm">
                @csrf @method('PATCH')
                <div class="row g-3">
                    <div class="col-12">
                        <div class="d-flex flex-column justify-content-center">
                            <label for="fileInput" class="text-center mx-auto">
                                <div class="image-upload-container">
                                    {{-- Gambar yang akan menjadi tombol dan preview --}}
                                    @if ($data['bundle']->flyer == 'uploads/images/product_bundles/bundle-1.png')
                                        <img src="{{ asset('uploads/images/product_bundles/bundle-1.png') }}"
                                            alt="Flyer Bundle" id="productImagePreview" class="img-fluid rounded-3 mb-2"
                                            style="width: 300px; cursor: pointer;">
                                    @else
                                        <img src="{{ asset('storage/' . $data['bundle']->flyer) }}" alt="Flyer Bundle"
                                            id="productImagePreview" class="img-fluid rounded-3 mb-2"
                                            style="width: 300px; cursor: pointer;">
                                    @endif

                                    {{-- Input file yang disembunyikan --}}
                                    <input type="file" name="image" id="fileInput" accept="image/*"
                                        class="d-none @error('image') is-invalid @enderror">
                                </div>
                            </label>
                            <span class="control-label mb-2 text-center">Flyer Bundle</span>
                            @error('image')
                                <small class="invalid-feedback">
                                    {{ $message }}
                                </small>
                            @enderror

                            {{-- Tombol Hapus Foto yang diposisikan absolut --}}
                            @if ($data['bundle']->flyer != 'uploads/images/product_bundles/bundle-1.png')
                                <button type="button" class="btn btn-danger btn-sm position-relative"
                                    style="bottom: 65.5px; left: 517px; width: fit-content; border-radius: 0px 0px 15px 0px;"
                                    id="delete-image-btn" data-id="{{ $data['bundle']->id }}">
                                    <i class="ti ti-trash"></i> Hapus Gambar
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nama Bundle</label>
                        <input type="text" class="form-control" name="bundle_name"
                            value="{{ $data['bundle']->bundle_name }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Deskripsi</label>
                        <input type="text" class="form-control" name="description"
                            value="{{ $data['bundle']->description }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control" name="start_date"
                            value="{{ $data['bundle']->start_date }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Berakhir</label>
                        <input type="date" class="form-control" name="end_date" value="{{ $data['bundle']->end_date }}"
                            required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Harga Spesial</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" class="form-control" id="specialPriceDisplay"
                                value="{{ number_format($data['bundle']->special_bundle_price, 0, ',', '.') }}" required>
                        </div>
                        <input type="hidden" name="special_bundle_price" id="specialPrice"
                            value="{{ $data['bundle']->special_bundle_price }}">
                        <small class="d-block mt-1" id="discountInfo"></small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Harga Asli (otomatis)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" class="form-control" id="originalPriceDisplay"
                                value="{{ number_format($data['bundle']->original_price, 0, ',', '.') }}" disabled>
                        </div>
                    </div>
                </div>

                <hr class="my-3">

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6>Item Produk</h6>
                    <button type="button" class="btn btn-sm btn-primary" id="addRow"><i class="ti ti-plus"></i> Tambah
                        Item</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered" id="itemsTable">
                        <thead>
                            <tr>
                                <th style="width:45%">Produk</th>
                                <th style="width:20%">Harga</th>
                                <th style="width:20%">Qty</th>
                                <th style="width:15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data['bundle']->product_bundle_items as $it)
                                <tr>
                                    <td>
                                        <select class="form-select product-select">
                                            <option value="">-- pilih produk --</option>
                                            @foreach ($data['products'] as $p)
                                                <option value="{{ $p->id }}" data-price="{{ $p->selling_price }}"
                                                    {{ $p->id == $it->product_id ? 'selected' : '' }}>
                                                    {{ $p->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="price-cell">Rp
                                        {{ number_format(optional($it->product)->selling_price ?? 0, 0, ',', '.') }}</td>
                                    <td><input type="number" class="form-control qty-input" value="{{ $it->quantity }}"
                                            min="1"></td>
                                    <td class="text-center"><button type="button"
                                            class="btn btn-sm btn-outline-danger remove-row"><i
                                                class="ti ti-trash"></i></button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {

            $('#delete-image-btn').on('click', function(e) {
                e.preventDefault();

                var flyerId = $(this).data('id');
                var deleteImageUrl = "{{ route('owner.bundle.deleteImage', ':id') }}";
                deleteImageUrl = deleteImageUrl.replace(':id', flyerId);

                if (confirm(
                        'Apakah Anda yakin ingin menghapus flyer bundle ini? Tindakan ini tidak dapat dibatalkan!'
                    )) {
                    $.ajax({
                        url: deleteImageUrl,
                        type: 'PUT', // Menggunakan metode HTTP DELETE
                        data: {
                            _token: "{{ csrf_token() }}" // Kirim CSRF token untuk keamanan Laravel
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#productImagePreview').attr('src', "{{ asset('') }}" +
                                    response
                                    .flyer);
                                toastr.success(response.success);
                            } else if (response.error) {
                                toastr.error(response.error);
                            }
                        },
                        error: function(xhr) {
                            console.error('AJAX Error:', xhr.responseText);
                            toastr.error('Terjadi kesalahan saat menghapus flyer bundle.');
                        }
                    });
                }
            })

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

            const fmt = new Intl.NumberFormat('id-ID');
            const $spDisp = $('#specialPriceDisplay');
            const $spVal = $('#specialPrice');
            const $opDisp = $('#originalPriceDisplay');
            const $disc = $('#discountInfo');

            function parseRp(s) {
                return Number(String(s).replace(/[^\d]/g, '') || 0);
            }

            function rp(n) {
                return 'Rp ' + fmt.format(Number(n || 0));
            }

            function recalcOriginal() {
                let total = 0;
                $('#itemsTable tbody tr').each(function() {
                    const price = Number($(this).find('.product-select option:selected').data('price') || 0);
                    const qty = Number($(this).find('.qty-input').val() || 0);
                    total += price * qty;
                });
                $opDisp.val(rp(total));
                const sp = parseRp($spVal.val());
                if (total > 0 && sp > 0) {
                    const diff = ((total - sp) / total) * 100;
                    const sign = diff >= 0 ? 'lebih murah' : 'lebih mahal';
                    $disc.removeClass('text-success text-danger').addClass(diff >= 0 ? 'text-success' : 'text-danger')
                        .text(`${Math.abs(diff).toFixed(2)}% ${sign} dari harga asli`);
                } else {
                    $disc.text('').removeClass('text-success text-danger');
                }
                return total;
            }

            // init special price formatter
            $spDisp.on('input', function() {
                const num = parseRp($(this).val());
                $spVal.val(num);
                $(this).val(fmt.format(num));
                recalcOriginal();
            }).trigger('input');

            // change row events
            $(document).on('change', '.product-select', function() {
                const price = Number($(this).find('option:selected').data('price') || 0);
                $(this).closest('tr').find('.price-cell').text(rp(price));
                recalcOriginal();
            });
            $(document).on('input', '.qty-input', recalcOriginal);

            // add row
            $('#addRow').on('click', function() {
                const row = `
      <tr>
        <td>
          <select class="form-select product-select">
            <option value="">-- pilih produk --</option>
            @foreach ($data['products'] as $p)
              <option value="{{ $p->id }}" data-price="{{ $p->selling_price }}">{{ $p->name }}</option>
            @endforeach
          </select>
        </td>
        <td class="price-cell">Rp 0</td>
        <td><input type="number" class="form-control qty-input" value="1" min="1"></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="ti ti-trash"></i></button></td>
      </tr>`;
                $('#itemsTable tbody').append(row);
            });

            $(document).on('click', '.remove-row', function() {
                $(this).closest('tr').remove();
                recalcOriginal();
            });

            // serialize items on submit
            $('#editBundleForm').on('submit', function(e) {
                // bikin input hidden items[] biar sesuai validasi controller
                $('input[name^="items"]').remove(); // bersihin dulu
                const items = [];
                $('#itemsTable tbody tr').each(function() {
                    const pid = $(this).find('.product-select').val();
                    const qty = $(this).find('.qty-input').val();
                    if (pid) {
                        items.push({
                            product_id: pid,
                            quantity: qty
                        });
                    }
                });
                if (items.length === 0) {
                    e.preventDefault();
                    alert('Minimal 1 item produk.');
                    return false;
                }
                items.forEach((it, i) => {
                    $('<input>').attr({
                        type: 'hidden',
                        name: `items[${i}][product_id]`,
                        value: it.product_id
                    }).appendTo('#editBundleForm');
                    $('<input>').attr({
                        type: 'hidden',
                        name: `items[${i}][quantity]`,
                        value: it.quantity
                    }).appendTo('#editBundleForm');
                });
            });

            recalcOriginal();
        })();
    </script>
@endpush
