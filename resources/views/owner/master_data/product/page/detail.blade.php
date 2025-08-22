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

    <div class="shop-detail">
        <div class="card shadow-none border">
            <div class="card-body p-4">
                <a href="{{ route('owner.master_data.product.index') }}" class="btn btn-sm btn-primary mb-3"><i
                        class="ti ti-arrow-left"></i>
                    Kembali</a>

                <div class="row g-4">
                    <div class="col-lg-5">
                        <div class="item rounded overflow-hidden">
                            @if ($product->image == 'uploads/images/products/product-1.png')
                                <img src="{{ asset('uploads/images/products/product-1.png') }}" alt=""
                                    class="img-fluid">
                            @else
                                <img src="{{ asset('storage/' . $product->image) }}" alt="" class="img-fluid">
                            @endif
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="shop-content">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="fs-2 fw-bolder">Produk</span>
                            </div>

                            <h4 class="fw-semibold">{{ $product->name ?? '' }}</h4>

                            <div class="border-top border-bottom py-3 row">
                                <div class="col-12">
                                    <div class="row row-cols-2 row-cols-md-3 mb-3 mb-md-1">
                                        <p class="col-6 col-md-3 fw-bolder">Nama Brand</p>
                                        <p class="col-auto col-md-1">:</p>
                                        <p class="col-12 col-md-6">{{ $product->product_brand->name }}</p>
                                    </div>
                                    <div class="row row-cols-2 row-cols-md-3 mb-3 mb-md-1">
                                        <p class="col-6 col-md-3 fw-bolder">Minimum Selling Unit</p>
                                        <p class="col-auto col-md-1">:</p>
                                        <p class="col-12 col-md-6">{{ ucfirst($product->product_unit->name) }}</p>
                                    </div>
                                    <div class="row row-cols-2 row-cols-md-3 mb-3 mb-md-1">
                                        <p class="col-6 col-md-3 fw-bolder">Harga Jual(MSU)</p>
                                        <p class="col-auto col-md-1">:</p>
                                        <p class="col-12 col-md-6">
                                            {{ 'Rp ' . number_format($product->selling_price, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="row row-cols-2 row-cols-md-3 mb-3 mb-md-1">
                                        <p class="col-6 col-md-3 fw-bolder">Diskon Produk</p>
                                        <p class="col-auto col-md-1">:</p>
                                        <p class="col-12 col-md-6">{{ $product->discount * 100 . '%' }}</p>
                                    </div>
                                    @php
                                        $discount = $product->discount * $product->selling_price;
                                    @endphp
                                    <div class="row row-cols-2 row-cols-md-3 mb-3 mb-md-1">
                                        <p class="col-6 col-md-3 fw-bolder">Harga setelah diskon</p>
                                        <p class="col-auto col-md-1">:</p>
                                        <p class="col-12 col-md-6">
                                            {{ 'Rp ' . number_format($product->selling_price - $discount, 0, ',', '.') }}
                                        </p>
                                    </div>
                                    <div class="row row-cols-2 row-cols-md-3 mb-3 mb-md-1">
                                        <p class="col-6 col-md-3 fw-bolder">Didaftarkan Pada</p>
                                        <p class="col-auto col-md-1">:</p>
                                        <p class="col-12 col-md-6">
                                            {{ \Carbon\Carbon::parse($product->created_at)->format('d M Y, H:i:s') }}</p>
                                    </div>
                                    <div class="d-flex flex-column flex-md-row gap-2 mt-3">
                                        <a href="{{ route('owner.master_data.product.edit', $product->id) }}"
                                            class="btn btn-warning">
                                            <i class="ti ti-pencil"></i> Edit Produk
                                        </a>
                                        <form action="{{ route('owner.master_data.product.destroy', $product->id) }}"
                                            method="post" class="d-inline">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="btn btn-danger w-100"
                                                onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini? Tindakan ini tidak dapat dibatalkan!')">
                                                <i class="ti ti-trash"></i> Hapus Produk
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row justify-content-center align-items-center g-2">
                    <div class="col-12">
                        <h5 class="mt-3 fw-bolder">Konversi Unit</h4>
                            <div class="d-flex justify-content-end align-items-start g-2 w-full">
                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal"
                                    data-bs-target="#modalTambah">
                                    <i class="ti ti-plus"></i> &nbsp; Tambah konversi unit
                                </button>
                            </div>
                            <div class="table-rensponsive">
                                <table class="table table-sm table-bordered mt-2" id="table">
                                    <thead>
                                        <tr>
                                            <th class="text-center">No.</th>
                                            <th class="text-center">Nama Unit</th>
                                            <th class="text-center">Jumlah MSU</th>
                                            <th class="text-center">Opsi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($product->unit_convertions as $item)
                                            <tr>
                                                <td class="text-center">{{ $loop->iteration }}</td>
                                                <td class="text-center">{{ $item->from_unit->name }}</td>
                                                <td class="text-center">{{ $item->convertion_factor }}</td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <button class="btn btn-sm btn-warning"><i
                                                                class="ti ti-pencil"></i></a>
                                                            <button type="button" class="btn btn-sm btn-danger delete-btn"
                                                                data-id="$row->id'"><i class="ti ti-trash"></i></button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL TAMBAH --}}
    <div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="modalTambahLabel">Tambah Konversi Unit</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('owner.master_data.product.unit_convertion.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        {{-- Container untuk menampilkan pesan error --}}
                        <div id="errorContainer" class="alert alert-danger d-none">
                            <ul id="errorList" style="list-style-type: circle" class="px-3"></ul>
                        </div>

                        <div class="row justify-content-center align-items-center g-2">
                            <div class="col">
                                <label for="from_unit" class="control-label mb-2">Dari Unit :</label>
                                <select class="form-control" name="from_unit" id="from_unit">
                                    <option value="">Pilih Unit</option>
                                    @foreach ($data['unit_to_convert'] as $item)
                                        <option value="{{ $item->id }}">{{ ucfirst($item->name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col text-center mt-auto mb-1" style="max-width: fit-content">
                                <i class="ti ti-arrow-right" style="font-size: 1.5rem"></i>
                            </div>
                            <div class="col">
                                <label for="to_unit" class="control-label mb-2">Ke Unit :</label>
                                <input type="text" class="form-control" value="{{ $product->product_unit->name }}"
                                    disabled name="to_unit">
                            </div>
                            <div class="col text-center mt-auto mb-1" style="max-width: fit-content">
                                <i class="ti ti-equal" style="font-size: 1.5rem"></i>
                            </div>
                            <div class="col">
                                <label for="convertion_factor" class="control-label mb-2">Jumlah konversi :</label>
                                <input type="number" class="form-control" name="convertion_factor" min="0"
                                    oninput="this.value = Math.abs(this.value)">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Tutup</button>
                        <button type="Submit" class="btn btn-primary" id="btn-save">
                            <span class="spinner-border spinner-border-sm d-none" role="status"
                                aria-hidden="true"></span>
                            <span class="btn-text">Ubah</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL EDIT --}}
    <div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="modalEditLabel">Edit Konversi Unit</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        {{-- Container untuk menampilkan pesan error --}}
                        <div id="errorContainer" class="alert alert-danger d-none">
                            <ul id="errorList" style="list-style-type: circle" class="px-3"></ul>
                        </div>

                        <div class="row justify-content-center align-items-center g-2">
                            <div class="col">
                                <label for="from_unit" class="control-label mb-2">Dari Unit :</label>
                                <select class="form-control" name="from_unit" id="from_unit">
                                    <option value="">Pilih Unit</option>
                                    @foreach ($data['unit_to_convert'] as $item)
                                        <option value="{{ $item->id }}">{{ ucfirst($item->name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col text-center mt-auto mb-1" style="max-width: fit-content">
                                <i class="ti ti-arrow-right" style="font-size: 1.5rem"></i>
                            </div>
                            <div class="col">
                                <label for="to_unit" class="control-label mb-2">Ke Unit :</label>
                                <input type="text" class="form-control" value="{{ $product->product_unit->name }}"
                                    disabled name="to_unit">
                            </div>
                            <div class="col text-center mt-auto mb-1" style="max-width: fit-content">
                                <i class="ti ti-equal" style="font-size: 1.5rem"></i>
                            </div>
                            <div class="col">
                                <label for="convertion_factor" class="control-label mb-2">Jumlah konversi :</label>
                                <input type="number" class="form-control" name="convertion_factor" min="0"
                                    oninput="this.value = Math.abs(this.value)">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Tutup</button>
                        <button type="Submit" class="btn btn-primary" id="btn-save">
                            <span class="spinner-border spinner-border-sm d-none" role="status"
                                aria-hidden="true"></span>
                            <span class="btn-text">Simpan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        let dataTable
        $(document).ready(function() {
            dataTable = $('#table').DataTable({
                processing: true, // Menampilkan indikator loading
                serverSide: true, // Mode server-side processing
                ajax: {
                    url: "{{ route('owner.master_data.product.unit_convertion.data') }}", // Endpoint API untuk DataTables
                    type: 'GET',
                    data: function(d) {
                        d.productId = {{ $product->id }}
                    }
                    // Anda bisa menambahkan data tambahan ke request di sini jika diperlukan
                    // data: function (d) {
                    //     d.myCustomParam = 'someValue';
                    // }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'from_unit_name',
                        name: 'from_unit_name',
                        className: 'text-center'
                    },
                    {
                        data: 'convertion_factor',
                        name: 'convertion_factor',
                        className: 'text-center'
                    },
                    {
                        data: 'options',
                        name: 'options',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                ],
                order: [
                    [4, 'desc']
                ],
                layout: {
                    topStart: 'search',
                    topEnd: 'pageLength',
                    bottomStart: 'info',
                    bottomEnd: 'paging'
                },
                pageLength: 5,
                lengthMenu: [
                    [5, 10, -1],
                    ['5', '10', 'Semua']
                ],
                language: {
                    info: 'Menampilkan halaman _PAGE_ dari _PAGES_ Halaman',
                    infoEmpty: 'Tidak ada data tersedia',
                    infoFiltered: '(disaring dari total _MAX_ data)',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    zeroRecords: 'Data tidak ditemukan',
                    search: 'Cari :'
                },
                search: {
                    return: true
                }
            });

            // AJAX submit untuk form tambah unit produk
            $('#modalTambah form').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                var url = form.attr('action');
                var formData = new FormData(this);

                // Tambahkan product_id dan minimum_selling_unit_id ke FormData
                formData.append('product_id', '{{ $product->id }}');
                formData.append('minimum_selling_unit_id', '{{ $product->minimum_selling_unit_id }}');

                var btn = form.find('#btn-save');
                var btnText = btn.find('.btn-text');
                var spinner = btn.find('.spinner-border');
                // Reset error
                form.find('.is-invalid').removeClass('is-invalid');
                form.find('.invalid-feedback').remove();

                // --- PERBAIKAN DI SINI: Reset container error ---
                var errorContainer = $('#modalTambah #errorContainer');
                var errorList = $('#modalTambah #errorList');
                errorContainer.addClass('d-none');
                errorList.empty();

                // Set loading state
                btn.prop('disabled', true);
                spinner.removeClass('d-none');
                btnText.text('Menyimpan...');
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log(response);

                        // Reset loading state
                        btn.prop('disabled', false);
                        spinner.addClass('d-none');
                        btnText.text('Simpan');
                        if (response.success) {
                            toastr.success(response.success);
                            $('#modalTambah').modal('hide');
                            form[0].reset();
                            // Reload dataTable tanpa ajax, cukup panggil draw ulang
                            if (typeof dataTable !== 'undefined') {
                                dataTable.draw(false);
                            }
                        } else if (response.error) {
                            toastr.error(response.error);
                        }
                    },
                    error: function(xhr) {
                        // Reset loading state
                        btn.prop('disabled', false);
                        spinner.addClass('d-none');
                        btnText.text('Simpan');
                        if (xhr.status === 422) {
                            // --- PERBAIKAN DI SINI: Tampilkan error di container ---
                            var errors = xhr.responseJSON.errors;
                            errorContainer.removeClass('d-none'); // Tampilkan container error
                            $.each(errors, function(key, val) {
                                errorList.append('<li>' + val[0] +
                                    '</li>'); // Tambahkan setiap error ke list
                            });
                        } else {
                            toastr.error('Terjadi kesalahan saat menyimpan data.');
                        }
                    }
                });
            });

            $(document).on('click', '.edit-btn', function(e) {
                var id = $(this).data('id');
                var editUrl = "{{ route('owner.master_data.product.unit_convertion.edit', ':id') }}";
                editUrl = editUrl.replace(':id', id);
                // Reset form
                var form = $('#modalEdit form');
                form[0].reset();
                form.find('.is-invalid').removeClass('is-invalid');
                form.find('.invalid-feedback').remove();

                // Set action form update
                var updateUrl = "{{ route('owner.master_data.product.unit_convertion.update', ':id') }}/";
                updateUrl = updateUrl.replace(':id', id);
                form.attr('action', updateUrl);
                // Ambil data
                $.ajax({
                    url: editUrl,
                    type: 'GET',
                    success: function(response) {
                        if (response.unit_convertion) {
                            form.find('[name="from_unit"] option').each(function() {
                                if ($(this).val() == response.unit_convertion
                                    .from_unit_id) {
                                    $(this).prop('selected', true);
                                } else {
                                    $(this).prop('selected', false);
                                }
                            });
                            form.find('[name="convertion_factor"]').val(response.unit_convertion
                                .convertion_factor);
                            $('#modalEdit').modal('show');
                        } else if (response.error) {
                            toastr.error(response.error);
                            $('#modalEdit').modal('hide');
                        }
                    },
                    error: function() {
                        toastr.error('Gagal mengambil data unit produk.');
                        $('#modalEdit').modal('hide');
                    }
                });
            });

            // AJAX submit untuk form edit unit produk
            $('#modalEdit form').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                var url = form.attr('action');
                var formData = new FormData(this);
                var btn = form.find('#btn-save');
                var btnText = btn.find('.btn-text');
                var spinner = btn.find('.spinner-border');
                // Reset error
                form.find('.is-invalid').removeClass('is-invalid');
                form.find('.invalid-feedback').remove();

                // Log data form ke console (untuk debugging)
                for (var pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }

                // --- PERBAIKAN DI SINI: Reset container error ---
                var errorContainer = $('#modalEdit #errorContainer');
                var errorList = $('#modalEdit #errorList');
                errorContainer.addClass('d-none');
                errorList.empty();

                // Set loading state
                btn.prop('disabled', true);
                spinner.removeClass('d-none');
                btnText.text('Menyimpan...');
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        // Reset loading state
                        btn.prop('disabled', false);
                        spinner.addClass('d-none');
                        btnText.text('Ubah');
                        if (response.success) {
                            toastr.success(response.success);
                            $('#modalEdit').modal('hide');
                            form[0].reset();
                            dataTable.ajax.reload(null, false);
                        } else if (response.error) {
                            toastr.error(response.error);
                        }
                    },
                    error: function(xhr) {
                        // Reset loading state
                        btn.prop('disabled', false);
                        spinner.addClass('d-none');
                        btnText.text('Ubah');
                        if (xhr.status === 422) {
                            // --- PERBAIKAN DI SINI: Tampilkan error di container ---
                            var errors = xhr.responseJSON.errors;
                            errorContainer.removeClass('d-none'); // Tampilkan container error
                            $.each(errors, function(key, val) {
                                errorList.append('<li>' + val[0] +
                                    '</li>'); // Tambahkan setiap error ke list
                            });
                        } else {
                            toastr.error('Terjadi kesalahan saat mengupdate data.');
                        }
                    }
                });
            });

            $(document).on('click', '.delete-btn', function(e) {
                e.preventDefault();

                var unitConvertionId = $(this).data('id');
                var deleteUrl = "{{ route('owner.master_data.product.unit_convertion.destroy', ':id') }}";
                deleteUrl = deleteUrl.replace(':id', unitConvertionId);

                if (confirm(
                        'Apakah Anda yakin ingin menghapus konversi ini? Tindakan ini tidak dapat dibatalkan!'
                    )) {
                    $.ajax({
                        url: deleteUrl,
                        type: 'DELETE', // Menggunakan metode HTTP DELETE
                        data: {
                            _token: "{{ csrf_token() }}" // Kirim CSRF token untuk keamanan Laravel
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.success);
                                dataTable.ajax.reload(null,
                                    false); // Reload DataTables tanpa reset posisi halaman
                            } else if (response.error) {
                                toastr.error(response.error);
                            }
                        },
                        error: function(xhr) {
                            console.error('AJAX Error:', xhr.responseText);
                            toastr.error('Terjadi kesalahan saat menghapus konversi.');
                        }
                    });
                }
            })
        })
    </script>
@endpush
