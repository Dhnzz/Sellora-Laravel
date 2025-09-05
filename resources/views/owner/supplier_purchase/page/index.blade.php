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
                <a href="{{ route('owner.supplier_purchase.create') }}" class="btn btn-sm btn-success">
                    <i class="ti ti-plus"></i> &nbsp; Tambah Belanja
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered mt-4" id="supplier-purchase-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 5%">No</th>
                            <th class="text-center" style="width: 15%">Invoice</th>
                            <th class="text-center" style="width: 15%">Supplier</th>
                            <th class="text-center" style="width: 12%">Tanggal Belanja</th>
                            <th class="text-center" style="width: 10%">Jumlah Item</th>
                            <th class="text-center" style="width: 15%">Total Amount</th>
                            <th class="text-center" style="width: 10%">Aksi</th>
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
            dataTable = $('#supplier-purchase-table').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: "{{ route('owner.supplier_purchase.getAll') }}",
                    type: 'GET',
                    complete: function(jqXHR, textStatus) {
                        if (textStatus === 'success') {
                            if (jqXHR && jqXHR.responseJSON) {
                                console.log('Data supplier purchase:', jqXHR.responseJSON);
                            }
                        }
                    },
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'invoice_number',
                        name: 'invoice_number',
                        className: 'text-center'
                    },
                    {
                        data: 'supplier_name',
                        name: 'supplier_name',
                        className: 'text-center'
                    },
                    {
                        data: 'purchase_date_formatted',
                        name: 'purchase_date',
                        className: 'text-center'
                    },
                    {
                        data: 'item_count',
                        name: 'item_count',
                        className: 'text-center'
                    },
                    {
                        data: 'total_amount_formatted',
                        name: 'total_amount',
                        className: 'text-center'
                    },
                    {
                        data: 'options',
                        name: 'options',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                order: [
                    [3, 'desc']
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

                var purchaseId = $(this).data('id');
                var deleteUrl = "{{ route('owner.supplier_purchase.destroy', ':id') }}";
                deleteUrl = deleteUrl.replace(':id', purchaseId);

                if (confirm(
                        'Apakah Anda yakin ingin menghapus belanja ini? Tindakan ini tidak dapat dibatalkan!'
                    )) {
                    $.ajax({
                        url: deleteUrl,
                        type: 'DELETE',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            if (response.status) {
                                toastr.success(response.message);
                                dataTable.ajax.reload(null, false);
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function(xhr) {
                            console.error('AJAX Error:', xhr.responseText);
                            toastr.error('Terjadi kesalahan saat menghapus belanja.');
                        }
                    });
                }
            })
        });
    </script>
@endpush
