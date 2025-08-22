@extends('layouts.app')

@push('styles')
    <style>
        /* Di dalam <style> tag di Blade atau di file CSS Anda */
        .image-upload-container img {
            border: 2px dashed #ccc;
            /* Gaya border putus-putus untuk indikasi area upload */
            transition: all 0.2s ease-in-out;
        }

        .image-upload-container img:hover {
            border-color: #007bff;
            /* Warna border berubah saat di-hover */
            background-color: #f8f9fa;
            cursor: pointer;
            /* Mengubah kursor menjadi pointer saat di-hover */
        }
    </style>
@endpush

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
        <form action="{{ route('owner.master_data.product.update', $product->id) }}" method="post"
            enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="card-body">
                <a href="{{ route('owner.master_data.product.index') }}" class="btn btn-sm btn-primary mb-3"><i
                        class="ti ti-arrow-left"></i>
                    Kembali</a>
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="d-flex flex-column justify-content-center">
                            <label for="fileInput" class="text-center mx-auto">
                                <div class="image-upload-container">
                                    {{-- Gambar yang akan menjadi tombol dan preview --}}
                                    @if ($product->image == 'uploads/images/products/product-1.png')
                                        <img src="{{ asset('uploads/images/products/product-1.png') }}" alt="Gambar Produk"
                                            id="productImagePreview" class="img-fluid rounded-3 mb-2"
                                            style="width: 300px; cursor: pointer;">
                                    @else
                                        <img src="{{ asset('storage/' . $product->image) }}" alt="Gambar Produk"
                                            id="productImagePreview" class="img-fluid rounded-3 mb-2"
                                            style="width: 300px; cursor: pointer;">
                                    @endif

                                    {{-- Input file yang disembunyikan --}}
                                    <input type="file" name="image" id="fileInput" accept="image/*"
                                        class="d-none @error('image') is-invalid @enderror">
                                </div>
                            </label>
                            <span class="control-label mb-2 text-center">Gambar Produk</span>
                            @error('image')
                                <small class="invalid-feedback">
                                    {{ $message }}
                                </small>
                            @enderror

                            {{-- Tombol Hapus Foto yang diposisikan absolut --}}
                            @if ($product->image != 'uploads/images/products/product-1.png')
                                <button type="button" class="btn btn-danger btn-sm position-relative"
                                    style="bottom: 67px; left: 265px; width: fit-content; border-radius: 0px 0px 15px 0px;"
                                    id="delete-image-btn" data-id="{{ $product->id }}">
                                    <i class="ti ti-trash"></i> Hapus Gambar
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label mb-1">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ $product->name }}" />
                            @error('name')
                                <small class="invalid-feedback">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>
                        <div class="mb-3 row row-cols-1 row-cols-md-2">
                            <div class="col-12 col-md-6 mb-3 mb-md-0">
                                <label class="control-label mb-1">Brand Produk <span class="text-danger">*</span></label>
                                <select type="text" name="product_brand" id="selectBrands"
                                    class="form-control @error('product_brand') is-invalid @enderror"
                                    value="{{ old('product_brand') }}">
                                    <option value="">Pilih Brand</option>
                                    @foreach ($data['product_brands'] as $item)
                                        <option value="{{ $item->id }}"
                                            {{ $product->product_brand_id == $item->id ? 'selected' : '' }}>
                                            {{ ucfirst($item->name) }}</option>
                                    @endforeach
                                </select>
                                @error('product_brand')
                                    <small class="invalid-feedback">
                                        {{ $message }}
                                    </small>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="control-label mb-1">MSU Produk <span class="text-danger">*</span></label>
                                <select type="text" name="product_unit" id="selectUnits"
                                    class="form-control @error('product_unit') is-invalid @enderror"
                                    value="{{ old('product_unit') }}">
                                    <option value="">Pilih MSU</option>
                                    @foreach ($data['product_units'] as $item)
                                        <option value="{{ $item->id }}"
                                            {{ $product->minimum_selling_unit_id == $item->id ? 'selected' : '' }}>
                                            {{ ucfirst($item->name) }}</option>
                                    @endforeach
                                </select>
                                @error('product_unit')
                                    <small class="invalid-feedback">
                                        {{ $message }}
                                    </small>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="control-label mb-1">Harga Jual (MSU) <span class="text-danger">*</span></label>
                            <div class="input-group mb-3">
                                <span class="input-group-text">Rp.</span>
                                <input type="text" id="selling_price"
                                    class="form-control @error('selling_price') is-invalid @enderror format-ribuan"
                                    value="{{ $product->selling_price }}" data-target="selling_price_raw">
                                <span class="input-group-text">.00</span>
                                @error('selling_price')
                                    <small class="invalid-feedback">
                                        {{ $message }}
                                    </small>
                                @enderror
                            </div>
                            <input type="hidden" name="selling_price" id="selling_price_raw">
                        </div>
                        <div class="mb-3">
                            <label class="control-label mb-1">Stok (Opsional)</label>
                            <div class="input-group mb-3">
                                <input type="text" name="stock"
                                    class="form-control @error('stock') is-invalid @enderror"
                                    value="{{ $product->stock->quantity ?? 0 }}">
                                <span class="input-group-text" id="stockInputGroup">MSU</span>
                            </div>
                            @error('stock')
                                <small class="invalid-feedback">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <div class="card-body border-top">
                    <button type="submit" class="btn btn-success rounded-pill px-4">
                        <div class="d-flex align-items-center">
                            <i class="ti ti-device-floppy me-1 fs-4"></i>
                            Simpan
                        </div>
                    </button>
                    <button type="reset" class="btn btn-danger rounded-pill px-4 ms-2 text-white">
                        Cancel
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
@push('scripts')
    <script>
        // Fungsi untuk memformat angka dengan titik sebagai pemisah ribuan
        function formatAngkaRibuan(angka) {
            // 1. Ubah ke string dan hilangkan semua karakter selain angka (digit 0-9)
            var cleaned = ('' + angka).replace(/[^\d]/g, '');

            if (cleaned === '') {
                return '';
            }

            // 2. Tambahkan titik sebagai pemisah ribuan
            return cleaned.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        $(document).ready(function() {
            // Menghubungkan klik pada gambar ke input file yang tersembunyi
            // $('#productImagePreview').on('click', function() {
            //     $('#fileInput').click();
            // });

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
                        "{{ asset('uploads/images/products/product-1.png') }}");
                    $('#productImagePreview').css('border-color', '#ccc');
                }
            });

            // --- LOGIKA UTAMA UNTUK SEMUA INPUT FORMAT RIBUAN ---
            function setupRibuanInput(inputElement) {
                var input = $(inputElement);
                var rawValueInputId = input.data('target');
                var rawValueInput = $('#' + rawValueInputId);

                // --- Jalankan pada saat inisialisasi (halaman dimuat) ---
                var initialValue = input.val();
                var cleanedInitialValue = initialValue.replace(/[^\d]/g, '');
                rawValueInput.val(cleanedInitialValue); // Set nilai hidden input
                input.val(formatAngkaRibuan(initialValue)); // Format nilai di input yang terlihat

                // --- Event Listener untuk perubahan saat mengetik ---
                input.on('input', function(e) {
                    var value = input.val();

                    // Simpan nilai non-formatted ke input hidden
                    var cleanedValue = value.replace(/[^\d]/g, '');
                    rawValueInput.val(cleanedValue);

                    // Format nilai di input yang terlihat
                    var formattedValue = formatAngkaRibuan(value);

                    // Pertahankan posisi kursor saat mengetik
                    var oldLength = value.length;
                    var newLength = formattedValue.length;
                    var cursorPos = input[0].selectionStart;

                    input.val(formattedValue);

                    input[0].setSelectionRange(
                        cursorPos + (newLength - oldLength),
                        cursorPos + (newLength - oldLength)
                    );
                });
            }
            // --- Panggil fungsi setup untuk setiap input yang diformat ---
            $('.format-ribuan').each(function() {
                setupRibuanInput(this);
            });

            // Mengatur agar form submit mengambil nilai dari hidden input
            $('form').on('submit', function() {
                // Hapus input yang diformat dari data yang dikirim agar tidak ada duplikasi
                // Atau pastikan hanya hidden input yang memiliki `name`
                $('.format-ribuan').prop('disabled', true);
            });

            $('#delete-image-btn').on('click', function(e) {
                e.preventDefault();

                var productId = $(this).data('id');
                var deleteImageUrl = "{{ route('owner.master_data.product.deleteImage', ':id') }}";
                deleteImageUrl = deleteImageUrl.replace(':id', productId);

                if (confirm(
                        'Apakah Anda yakin ingin menghapus gambar produk ini? Tindakan ini tidak dapat dibatalkan!'
                    )) {
                    $.ajax({
                        url: deleteImageUrl,
                        type: 'PUT', // Menggunakan metode HTTP DELETE
                        data: {
                            _token: "{{ csrf_token() }}" // Kirim CSRF token untuk keamanan Laravel
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#productImagePreview').attr('src', "{{ asset('') }}" + response
                                    .image);
                                toastr.success(response.success);
                            } else if (response.error) {
                                toastr.error(response.error);
                            }
                        },
                        error: function(xhr) {
                            console.error('AJAX Error:', xhr.responseText);
                            toastr.error('Terjadi kesalahan saat menghapus gambar produk.');
                        }
                    });
                }
            })

            $('#selectUnits').on('change', function(e) {
                var selectedText = $(this).find('option:selected').text();
                $('#stockInputGroup').text(selectedText)
            })
            $('#selectBrands').select2()
            $('#selectUnits').select2()

        });
    </script>
@endpush
