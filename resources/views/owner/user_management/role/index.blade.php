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

            <div class="table-responsive">
                <table class="table table-sm table-bordered mt-4" id="table">
                    <thead>
                        <tr>
                            <th class="text-center">No</th>
                            <th class="text-center">Nama</th>
                            <th class="text-center">Email</th>
                            <th class="text-center">Nomor Telepon</th>
                            <th class="text-center">Role</th>
                            <th class="text-center">Tanggal Dibuat</th>
                            <th class="text-center">Opsi</th>
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
                    url: "{{ route('owner.user_management.role.data') }}", // Endpoint API untuk DataTables
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
                        data: 'email',
                        name: 'email',
                        className: 'text-center'
                    },
                    {
                        data: 'phone',
                        name: 'phone',
                        orderable: true,
                        searchable: true,
                        className: 'text-center'
                    }, // orderable:true, searchable:true
                    {
                        data: 'role',
                        name: 'role',
                        orderable: true,
                        searchable: true,
                        className: 'text-center',
                        render: function(data, type, row) {
                            if (data === 'Admin') {
                                return '<small class="badge bg-primary" style="width:100%">Admin</small>';
                            } else if (data === 'Owner') {
                                return '<small class="badge bg-danger" style="width:100%">Owner</small>';
                            } else if (data === 'Customer') {
                                return '<small class="badge bg-success" style="width:100%">Customer</small>';
                            } else if (data === 'Sales') {
                                return '<small class="badge bg-warning" style="width:100%">Sales Agent</small>';
                            } else if (data === 'Warehouse') {
                                return '<small class="badge bg-info" style="width:100%">Warehouse Manager</small>';
                            }
                            return data;
                        }
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
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
        });
    </script>
@endpush
