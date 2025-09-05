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
        <form id="supplier-purchase-edit-form">
            @csrf
            @method('PUT')
            <div class="card-body">
                <a href="{{ route('owner.supplier_purchase.show', $supplierPurchase->id) }}"
                    class="btn btn-sm btn-primary mb-3">
                    <i class="ti ti-arrow-left"></i> Kembali
                </a>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="control-label mb-1">Supplier <span class="text-danger">*</span></label>
                            <select class="form-select" name="supplier_id" id="supplier_id" required>
                                <option value="">Pilih Supplier</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}"
                                        {{ $supplierPurchase->supplier_id == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="control-label mb-1">Tanggal Belanja <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="purchase_date" id="purchase_date"
                                value="{{ $supplierPurchase->purchase_date }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="control-label mb-1">Nomor Invoice <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="invoice_number" id="invoice_number"
                                value="{{ $supplierPurchase->invoice_number }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="control-label mb-1">Catatan</label>
                            <textarea class="form-control" name="notes" id="notes" rows="3">{{ $supplierPurchase->notes }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="control-label mb-1">Produk yang Dibeli <span class="text-danger">*</span></label>
                    <div id="product-items">
                        @foreach ($supplierPurchase->supplier_purchase_item as $index => $item)
                            <div class="product-item border rounded p-2 mb-3">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-2">
                                            <label class="form-label">Produk</label>
                                            <select class="form-select form-select-sm product-select"
                                                name="items[{{ $index }}][product_id]" required>
                                                <option value="">Pilih Produk</option>
                                                @foreach ($products as $product)
                                                    <option value="{{ $product->id }}"
                                                        data-unit="{{ $product->product_unit->name ?? 'Unit' }}"
                                                        data-price="{{ $product->selling_price }}"
                                                        {{ $item->product_id == $product->id ? 'selected' : '' }}>
                                                        {{ $product->name }} -
                                                        {{ $product->product_brand->name ?? 'N/A' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-2">
                                            <label class="form-label">Jumlah</label>
                                            <div class="input-group input-group-sm">
                                                <button type="button" class="btn btn-outline-secondary btn-decrease"
                                                    data-index="{{ $index }}">
                                                    <i class="ti ti-minus"></i>
                                                </button>
                                                <input type="number" class="form-control text-center quantity-input"
                                                    name="items[{{ $index }}][quantity]"
                                                    value="{{ $item->quantity }}" min="1" required>
                                                <button type="button" class="btn btn-outline-secondary btn-increase"
                                                    data-index="{{ $index }}">
                                                    <i class="ti ti-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-2">
                                            <label class="form-label">Harga Beli per Unit</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Rp</span>
                                                <input type="text" class="form-control format-ribuan price-input-visible"
                                                    value="{{ (int) $item->price }}" placeholder="0" min="0"
                                                    required data-target="price_raw">
                                            </div>
                                            <input type="hidden" name="items[{{ $index }}][price]"
                                                class="price-input" id="price_raw">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-2">
                                            <label class="form-label">Subtotal</label>
                                            <input type="text" class="form-control form-control-sm subtotal-input"
                                                readonly
                                                value="Rp {{ number_format($item->quantity * $item->price, 0, ',', '.') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <div class="mb-2">
                                            <label class="form-label">&nbsp;</label>
                                            @if ($index > 0)
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-danger btn-remove-item"
                                                    data-index="{{ $index }}">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" class="btn btn-outline-primary" id="btn-add-item">
                        <i class="ti ti-plus"></i> Tambah Produk
                    </button>
                </div>

                <div class="row">
                    <div class="col-md-6 offset-md-6">
                        <div class="mb-3">
                            <label class="form-label">Total Amount</label>
                            <input type="text" class="form-control" id="total_amount"
                                value="Rp {{ number_format($supplierPurchase->total_amount, 0, ',', '.') }}" readonly>
                        </div>
                    </div>
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn btn-primary">Update Belanja</button>
                    <a href="{{ route('owner.supplier_purchase.show', $supplierPurchase->id) }}"
                        class="btn btn-secondary">Batal</a>
                </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // UTILITIES
            function formatAngkaRibuan(angka) {
                // 1. Ubah ke string dan hilangkan semua karakter selain angka (digit 0-9)
                var cleaned = ('' + angka).replace(/[^\d]/g, '');

                if (cleaned === '') {
                    return '';
                }

                // 2. Tambahkan titik sebagai pemisah ribuan
                return cleaned.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            }

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
            $('.format-ribuan').each(function() {
                setupRibuanInput(this);
            });

            let itemIndex = {{ count($supplierPurchase->supplier_purchase_item) }};

            // Calculate subtotal for an item
            function calculateSubtotal(index) {
                var productItem = $(`.product-item:eq(${index})`);
                const quantity = $(`input[name="items[${index}][quantity]"]`).val();
                const price = $(`.product-item:eq(${index}) .price-input`).val();
                const subtotal = quantity * price;
                $(`.product-item:eq(${index}) .subtotal-input`).val('Rp ' + subtotal.toLocaleString('id-ID'));
                calculateTotal();
            }

            // Calculate total amount
            function calculateTotal() {
                let total = 0;
                // Iterasi melalui setiap item produk yang ada
                $('.product-item').each(function(index) {
                    // Cari input quantity dan price di dalam product-item saat ini
                    const quantity = $(this).find('.quantity-input').val() || 0;
                    const price = $(this).find('.price-input').val() || 0;

                    // Pastikan quantity dan price adalah angka sebelum dihitung
                    const numericQuantity = parseFloat(quantity) || 0;
                    const numericPrice = parseFloat(price) || 0;

                    total += numericQuantity * numericPrice;
                });
                // $('.price-input').each(function(index) {
                //     const quantity = $(this).siblings('.quantity-input').val() || 0;
                //     const price = $(this).val() || 0;
                //     total += quantity * price;
                //     console.log(quantity);

                // });
                $('#total_amount').val('Rp ' + total.toLocaleString('id-ID'));
            }

            // Add new product item
            $('#btn-add-item').click(function() {
                const newItem = `
            <div class="product-item border rounded p-3 mb-3">
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-2">
                            <label class="form-label">Produk</label>
                            <select class="form-select form-select-sm product-select" name="items[${itemIndex}][product_id]" required>
                                <option value="">Pilih Produk</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" 
                                            data-unit="{{ $product->product_unit->name ?? 'Unit' }}"
                                            data-price="{{ $product->selling_price }}">
                                        {{ $product->name }} - {{ $product->productBrand->name ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-2">
                            <label class="form-label">Jumlah</label>
                            <div class="input-group input-group-sm">
                                <button type="button" class="btn btn-outline-secondary btn-decrease" data-index="${itemIndex}">
                                    <i class="ti ti-minus"></i>
                                </button>
                                <input type="number" class="form-control text-center quantity-input" 
                                       name="items[${itemIndex}][quantity]" value="1" min="1" required>
                                <button type="button" class="btn btn-outline-secondary btn-increase" data-index="${itemIndex}">
                                    <i class="ti ti-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-2">
                            <label class="form-label">Harga Beli per Unit</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control price-input" 
                                       name="items[${itemIndex}][price]" placeholder="0" min="0" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-2">
                            <label class="form-label">Subtotal</label>
                            <input type="text" class="form-control form-control-sm subtotal-input" readonly>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="mb-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item" data-index="${itemIndex}">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

                $('#product-items').append(newItem);
                itemIndex++;
            });

            // Remove product item
            $(document).on('click', '.btn-remove-item', function() {
                if ($('.product-item').length > 1) {
                    $(this).closest('.product-item').remove();
                    calculateTotal();
                }
            });

            // Increase quantity
            $(document).on('click', '.btn-increase', function() {
                const index = $(this).data('index');
                const input = $(`input[name="items[${index}][quantity]"]`);
                input.val(parseInt(input.val()) + 1);
                calculateSubtotal(index);
            });

            // Decrease quantity
            $(document).on('click', '.btn-decrease', function() {
                const index = $(this).data('index');
                const input = $(`input[name="items[${index}][quantity]"]`);
                const currentVal = parseInt(input.val());
                if (currentVal > 1) {
                    input.val(currentVal - 1);
                    calculateSubtotal(index);
                }
            });

            // Handle quantity and price changes
            $(document).on('input', '.quantity-input, .price-input-visible', function() {
                const index = $(this).closest('.product-item').index();
                calculateSubtotal(index);
            });

            // Handle product selection
            $(document).on('change', '.product-select', function() {
                const selectedOption = $(this).find('option:selected');
                const price = selectedOption.data('price');
                const index = $(this).closest('.product-item').index();

                if (price) {
                    $(this).closest('.product-item').find('.price-input').val(price);
                    calculateSubtotal(index);
                }
            });

            // Form submission
            $('#supplier-purchase-edit-form').submit(function(e) {
                e.preventDefault();

                // Validate form
                if (!this.checkValidity()) {
                    e.stopPropagation();
                    $(this).addClass('was-validated');
                    return;
                }

                const formData = new FormData(this);

                $.ajax({
                    url: '{{ route('owner.supplier_purchase.update', $supplierPurchase->id) }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.status) {
                            toastr.success(response.message);
                            setTimeout(function() {
                                window.location.href =
                                    '{{ route('owner.supplier_purchase.show', $supplierPurchase->id) }}';
                            }, 1500);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            Object.keys(errors).forEach(function(key) {
                                toastr.error(errors[key][0]);
                            });
                        } else {
                            toastr.error('Terjadi kesalahan saat mengupdate data');
                        }
                    }
                });
            });

            // Initialize subtotals for existing items
            @foreach ($supplierPurchase->supplier_purchase_item as $index => $item)
                calculateSubtotal({{ $index }});
            @endforeach
        });
    </script>
@endpush
