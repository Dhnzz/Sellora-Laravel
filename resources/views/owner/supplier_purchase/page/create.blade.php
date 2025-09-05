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
        <form id="supplier-purchase-form">
            @csrf
            <div class="card-body">
                <a href="{{ route('owner.supplier_purchase.index') }}" class="btn btn-sm btn-primary mb-3">
                    <i class="ti ti-arrow-left"></i> Kembali
                </a>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="control-label mb-1">Supplier <span class="text-danger">*</span></label>
                            <select class="form-select" name="supplier_id" id="supplier_id" required>
                                <option value="">Pilih Supplier</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="control-label mb-1">Tanggal Belanja <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="purchase_date" id="purchase_date"
                                value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="control-label mb-1">Nomor Invoice <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="invoice_number" id="invoice_number"
                                placeholder="Masukkan nomor invoice" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="control-label mb-1">Catatan</label>
                            <textarea class="form-control" name="notes" id="notes" rows="3" placeholder="Catatan tambahan"></textarea>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="control-label mb-1">Produk yang Dibeli <span class="text-danger">*</span></label>
                    <div id="product-items">
                        <div class="product-item border rounded p-2 mb-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-2">
                                        <label class="control-label mb-1">Produk</label>
                                        <select class="form-select form-select-sm product-select"
                                            name="items[0][product_id]" required>
                                            <option value="">Pilih Produk</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}"
                                                    data-unit="{{ $product->product_unit->name ?? 'Unit' }}"
                                                    data-price="{{ $product->selling_price }}">
                                                    {{ $product->name }} -
                                                    {{ $product->product_brand->name ?? 'N/A' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mb-2">
                                        <label class="control-label mb-1">Jumlah</label>
                                        <div class="input-group input-group-sm">
                                            <button type="button" class="btn btn-outline-secondary btn-decrease"
                                                data-index="0">
                                                <i class="ti ti-minus"></i>
                                            </button>
                                            <input type="number" class="form-control text-center quantity-input"
                                                name="items[0][quantity]" value="1" min="1" required
                                                style="width: 50px">
                                            <button type="button" class="btn btn-outline-secondary btn-increase"
                                                data-index="0">
                                                <i class="ti ti-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-2">
                                        <label class="control-label mb-1">Harga Beli per Unit</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" class="form-control price-input" name="items[0][price]"
                                                placeholder="0" min="0" step="0.01" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mb-2">
                                        <label class="control-label mb-1">Subtotal</label>
                                        <input type="text" class="form-control form-control-sm subtotal-input"
                                            readonly>
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <div class="mb-2">
                                        <label class="control-label mb-1">&nbsp;</label>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item"
                                            data-index="0" style="display: none;">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-outline-primary" id="btn-add-item">
                        <i class="ti ti-plus"></i> Tambah Produk
                    </button>
                </div>

                <div class="row">
                    <div class="col-md-6 offset-md-6">
                        <div class="mb-3">
                            <label class="control-label mb-1">Total Amount</label>
                            <input type="text" class="form-control" id="total_amount" readonly>
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
        $(document).ready(function() {
            let itemIndex = 1;

            // Generate invoice number
            function generateInvoiceNumber() {
                const today = new Date();
                const dateStr = today.getFullYear().toString() +
                    (today.getMonth() + 1).toString().padStart(2, '0') +
                    today.getDate().toString().padStart(2, '0');
                const randomNum = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
                $('#invoice_number').val('INV-SUP-' + dateStr + '-' + randomNum);
            }

            // Calculate subtotal for an item
            function calculateSubtotal(index) {
                const quantity = $(`input[name="items[${index}][quantity]"]`).val();
                const price = $(`.product-item:eq(${index}) .price-input`).val();
                const subtotal = quantity * price;
                $(`.product-item:eq(${index}) .subtotal-input`).val('Rp ' + subtotal.toLocaleString('id-ID'));
                calculateTotal();
            }

            // Calculate total amount
            function calculateTotal() {
                let total = 0;
                $('.price-input').each(function(index) {
                    const quantity = $(this).closest('.col-md-3')
                        .siblings('.col-md-2')
                        .find('.quantity-input')
                        .val() || 0;
                    const price = $(this).val() || 0;
                    total += quantity * price;
                });
                $('#total_amount').val('Rp ' + total.toLocaleString('id-ID'));
            }

            // Add new product item
            $('#btn-add-item').click(function() {
                const newItem = `
            <div class="product-item border rounded p-2 mb-3">
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-2">
                            <label class="control-label mb-1">Produk</label>
                            <select class="form-select form-select-sm product-select" name="items[${itemIndex}][product_id]" required>
                                <option value="">Pilih Produk</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" 
                                            data-unit="{{ $product->product_unit->name ?? 'Unit' }}"
                                            data-price="{{ $product->selling_price }}">
                                        {{ $product->name }} - {{ $product->product_brand->name ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-2">
                            <label class="control-label mb-1">Jumlah</label>
                            <div class="input-group input-group-sm">
                                <button type="button" class="btn btn-outline-secondary btn-decrease" data-index="${itemIndex}">
                                    <i class="ti ti-minus"></i>
                                </button>
                                <input type="number" class="form-control text-center quantity-input" 
                                       name="items[${itemIndex}][quantity]" value="1" min="1" required style="width:50px">
                                <button type="button" class="btn btn-outline-secondary btn-increase" data-index="${itemIndex}">
                                    <i class="ti ti-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-2">
                            <label class="control-label mb-1">Harga Beli per Unit</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control price-input" 
                                       name="items[${itemIndex}][price]" placeholder="0" min="0" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-2">
                            <label class="control-label mb-1">Subtotal</label>
                            <input type="text" class="form-control form-control-sm subtotal-input" readonly>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="mb-2">
                            <label class="control-label mb-1">&nbsp;</label>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item" data-index="${itemIndex}">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

                $('#product-items').append(newItem);

                // Show remove button for first item if there are multiple items
                if ($('.product-item').length > 1) {
                    $('.product-item:first .btn-remove-item').show();
                }

                itemIndex++;
            });

            // Remove product item
            $(document).on('click', '.btn-remove-item', function() {
                if ($('.product-item').length > 1) {
                    $(this).closest('.product-item').remove();
                    calculateTotal();

                    // Hide remove button for first item if only one item remains
                    if ($('.product-item').length === 1) {
                        $('.product-item:first .btn-remove-item').hide();
                    }
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
            $(document).on('input', '.quantity-input, .price-input', function() {
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
            $('#supplier-purchase-form').submit(function(e) {
                e.preventDefault();

                // Validate form
                if (!this.checkValidity()) {
                    e.stopPropagation();
                    $(this).addClass('was-validated');
                    return;
                }

                const formData = new FormData(this);

                $.ajax({
                    url: '{{ route('owner.supplier_purchase.store') }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.status) {
                            toastr.success(response.message);
                            setTimeout(function() {
                                window.location.href =
                                    '{{ route('owner.supplier_purchase.index') }}';
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
                            toastr.error('Terjadi kesalahan saat menyimpan data');
                        }
                    }
                });
            });

            // Initialize
            generateInvoiceNumber();
            calculateSubtotal(0);
        });
    </script>
@endpush
