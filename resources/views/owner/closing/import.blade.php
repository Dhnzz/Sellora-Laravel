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
            <form method="POST" action="{{ route('owner.closing.import.do') }}" enctype="multipart/form-data"
                class="row g-3">
                @csrf
                <div class="col-12">
                    <label class="form-label">Pilih File (.xlsx / .csv)</label>
                    <input type="file" name="file" class="form-control" accept=".xlsx,.csv,.txt" required>
                    <small class="text-muted">
                        Header kolom wajib: <code>Tahun</code>, <code>Bulan</code>, <code>Total Profit</code>.
                    </small>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary">Import</button>
                    <a href="{{ route('owner.closing.create') }}" class="btn btn-outline-secondary">Kembali ke Tutup
                        Buku</a>
                </div>
            </form>
        </div>
    </div>

    <div class="mt-3">
        <div class="small text-muted">
            Contoh format:
            <pre class="mb-0">Tahun,Bulan,Total Profit
2024,11,125000000
2024,12,99000000
2025,1,142500000
            </pre>
        </div>
    </div>
@endsection
