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
            <div class="row w-100">
                <div class="col-auto ms-auto">
                    <a href="{{ route('owner.closing.create') }}" class="btn btn-sm btn-success btn-block"><i
                            class="ti ti-plus"></i> Tutup Bulan ini</a>
                    <a href="{{ route('owner.closing.import.form') }}" class="btn btn-sm btn-warning"><i class="ti ti-upload"></i> Import Data Tutup Buku</a>

                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm" id="dataTable">
                    <thead>
                        <tr>
                            <th class="text-center">No.</th>
                            <th class="text-center">Tahun</th>
                            <th class="text-center">Bulan</th>
                            <th class="text-center">Jumlah Pendapatan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data['monthly_closings'] as $item)
                            <tr>
                                <td style="width: 10%">{{ $loop->iteration }}</td>
                                <td class="text-center">{{ $item->year }}</td>
                                <td>{{ $item->month }}</td>
                                <td>{{ $item->total_profit }}</td>
                                <td>
                                    <div class="d-flex gap-2 justify-content-center">
                                        <a href="{{ route('owner.closing.edit', $item->id) }}"
                                            class="btn btn-sm btn-warning"><i class="ti ti-pencil"></i></a>
                                        <form action="{{ route('owner.closing.destroy', $item->id) }}" method="post">
                                            @csrf
                                            @method('DELETE')
                                            <button style="submit" class="btn btn-sm btn-danger"><i
                                                    class="ti ti-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                language: {
                    "sEmptyTable": "Tidak ada data yang tersedia pada tabel ini",
                    "sProcessing": "Sedang memproses...",
                    "sLengthMenu": "Tampilkan _MENU_ entri",
                    "sZeroRecords": "Tidak ditemukan data yang sesuai",
                    "sInfo": "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                    "sInfoEmpty": "Menampilkan 0 sampai 0 dari 0 entri",
                    "sInfoFiltered": "(disaring dari _MAX_ entri keseluruhan)",
                    "sInfoPostFix": "",
                    "sSearch": "Cari:",
                    "sUrl": "",
                },
                "columnDefs": [{
                        "targets": 2, // Kolom Bulan
                        "render": function(data, type, row, meta) {
                            // data = angka bulan (1-12)
                            const bulanIndo = [
                                '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                                'Juli', 'Agustus', 'September', 'Oktober', 'November',
                                'Desember'
                            ];
                            let bulan = parseInt(data);
                            return bulanIndo[bulan] || data;
                        }
                    },
                    {
                        "targets": 3, // Kolom Total Profit
                        "render": function(data, type, row, meta) {
                            // Format ke rupiah
                            let number = parseFloat(data);
                            if (isNaN(number)) return data;
                            return 'Rp ' + number.toLocaleString('id-ID', {
                                minimumFractionDigits: 0
                            });
                        }
                    }
                ]
            })
        })
    </script>
@endpush
