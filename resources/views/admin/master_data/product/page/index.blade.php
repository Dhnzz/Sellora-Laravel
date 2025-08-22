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
                <a href="{{ route('owner.master_data.product.create') }}" class="btn btn-sm btn-success">
                    <i class="ti ti-plus"></i> &nbsp; Tambah produk
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered mt-4" id="table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 5%">No</th>
                            <th class="text-center" style="width: 30%">Nama Produk</th>
                            <th class="text-center" style="width: 10%">MSU</th>
                            <th class="text-center" style="width: 30%">Harga Jual</th>
                            <th class="text-center" style="width: 30%">Stock</th>
                            <th class="text-center" style="width: 20%">Opsi</th>
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
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
                    url: "{{ route('owner.master_data.product.data') }}", // Endpoint API untuk DataTables
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
                        data: 'product_name',
                        name: 'product_name',
                        className: 'text-center'
                    },
                    {
                        data: 'msu_name',
                        name: 'msu_name',
                        className: 'text-center',
                        render: function(data, type, row) {
                            // Mengubah huruf pertama menjadi kapital
                            if (typeof data === 'string' && data.length > 0) {
                                return data.charAt(0).toUpperCase() + data.slice(1);
                            }
                            return data;
                        }
                    },
                    {
                        data: 'selling_price',
                        name: 'selling_price',
                        className: 'text-center'
                    },
                    {
                        data: 'quantity',
                        name: 'quantity',
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

                var productId = $(this).data('id');
                var deleteUrl = "{{ route('owner.master_data.product.destroy', ':id') }}";
                deleteUrl = deleteUrl.replace(':id', productId);

                if (confirm(
                        'Apakah Anda yakin ingin menghapus produk ini? Tindakan ini tidak dapat dibatalkan!'
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
                            toastr.error('Terjadi kesalahan saat menghapus produk.');
                        }
                    });
                }
            })
        });
    </script>
@endpush
