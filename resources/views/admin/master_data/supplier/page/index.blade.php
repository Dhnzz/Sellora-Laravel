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
            <div class="d-flex justify-content-end align-items-start g-2 w-full">
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="ti ti-plus"></i> &nbsp; Tambah supplier
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered mt-4" id="table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 5%">No</th>
                            <th class="text-center" style="width: 30%">Nama</th>
                            <th class="text-center" style="width: 30%">Alamat</th>
                            <th class="text-center" style="width: 30%">Nomor Telepon</th>
                            <th class="text-center" style="width: 20%">Opsi</th>
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- MODAL TAMBAH --}}
    <div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="modalTambahLabel">Tambah Supplier</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('owner.master_data.supplier.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="" class="control-label mb-2">Nama supplier :</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name">
                            @error('name')
                                <small class="invalid-feedback">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="" class="control-label mb-2">Nomor Telepon :</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone">
                            @error('phone')
                                <small class="invalid-feedback">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="" class="control-label mb-2">Alamat :</label>
                            <textarea name="address" class="form-control @error('address') is-invalid @enderror" id=""></textarea>
                            @error('address')
                                <small class="invalid-feedback">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Tutup</button>
                        <button type="Submit" class="btn btn-primary" id="btn-save">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            <span class="btn-text">Simpan</span>
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
                    <h1 class="modal-title fs-5" id="modalEditLabel">Edit Brand Produk</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="" class="control-label mb-2">Nama supplier :</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                name="name">
                            @error('name')
                                <small class="invalid-feedback">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="" class="control-label mb-2">Nomor Telepon :</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                name="phone">
                            @error('phone')
                                <small class="invalid-feedback">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="" class="control-label mb-2">Alamat :</label>
                            <textarea name="address" class="form-control @error('address') is-invalid @enderror" id=""></textarea>
                            @error('address')
                                <small class="invalid-feedback">
                                    {{ $message }}
                                </small>
                            @enderror
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
                    url: "{{ route('admin.master_data.supplier.data') }}", // Endpoint API untuk DataTables
                    type: 'GET',
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
                        data: 'name',
                        name: 'name',
                        className: 'text-center'
                    },
                    {
                        data: 'address',
                        name: 'address',
                        className: 'text-center'
                    },
                    {
                        data: 'phone',
                        name: 'phone',
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

            $(document).on('click', '.delete-btn', function(e) {
                e.preventDefault();

                var supplierId = $(this).data('id');
                var deleteUrl = "{{ route('admin.master_data.supplier.destroy', ':id') }}";
                deleteUrl = deleteUrl.replace(':id', supplierId);

                if (confirm(
                        'Apakah Anda yakin ingin menghapus supplier ini? Tindakan ini tidak dapat dibatalkan!'
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
                            toastr.error('Terjadi kesalahan saat menghapus supplier.');
                        }
                    });
                }
            })

            // Handle klik tombol edit
            $(document).on('click', '.edit-btn', function(e) {
                var id = $(this).data('id');
                var editUrl = "{{ route('admin.master_data.supplier.edit', ':id') }}";
                editUrl = editUrl.replace(':id', id);
                // Reset form
                var form = $('#modalEdit form');
                form[0].reset();
                form.find('.is-invalid').removeClass('is-invalid');
                form.find('.invalid-feedback').remove();
                // Set action form update
                var updateUrl = "{{ route('admin.master_data.supplier.update', ':id') }}/";
                updateUrl = updateUrl.replace(':id', id);
                form.attr('action', updateUrl);
                // Ambil data
                $.ajax({
                    url: editUrl,
                    type: 'GET',
                    success: function(response) {
                        if (response.supplier) {
                            form.find('[name="name"]').val(response.supplier.name);
                            form.find('[name="phone"]').val(response.supplier.phone);
                            form.find('[name="address"]').val(response.supplier.address);
                            $('#modalEdit').modal('show');
                        } else if (response.error) {
                            toastr.error(response.error);
                            $('#modalEdit').modal('hide');
                        }
                    },
                    error: function() {
                        toastr.error('Gagal mengambil data supplier.');
                        $('#modalEdit').modal('hide');
                    }
                });
            });

            // AJAX submit untuk form edit brand produk
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
                        btnText.text('Simpan');
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
                        btnText.text('Simpan');
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, val) {
                                var input = form.find('[name="' + key + '"]');
                                input.addClass('is-invalid');
                                input.after('<small class="invalid-feedback">' + val[
                                    0] + '</small>');
                            });
                        } else {
                            toastr.error('Terjadi kesalahan saat mengupdate data.');
                        }
                    }
                });
            });

            // AJAX submit untuk form tambah brand produk
            $('#modalTambah form').on('submit', function(e) {
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
                        btnText.text('Simpan');
                        if (response.success) {
                            toastr.success(response.success);
                            $('#modalTambah').modal('hide');
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
                        btnText.text('Simpan');
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, val) {
                                var input = form.find('[name="' + key + '"]');
                                input.addClass('is-invalid');
                                input.after('<small class="invalid-feedback">' + val[
                                    0] + '</small>');
                            });
                        } else {
                            toastr.error('Terjadi kesalahan saat menyimpan data.');
                        }
                    }
                });
            });
        });
    </script>
@endpush
