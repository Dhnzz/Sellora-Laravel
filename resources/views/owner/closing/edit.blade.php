{{-- resources/views/owner/closing/create.blade.php --}}
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
        <form method="POST" action="{{ route('owner.closing.store') }}" class="row g-3">
            <div class="card-body">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Tahun</label>
                        <input type="number" name="year" class="form-control" value="{{ $data['monthly_closing']->year }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Bulan</label>
                        <input type="number" name="month" min="1" max="12" class="form-control"
                            value="{{ $data['monthly_closing']->month }}" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-5">
                        <label class="form-label">Total Keuntungan Bersih<span class="text-danger">*</span></label>
                        <div class="input-group mb-3">
                            <span class="input-group-text">Rp.</span>
                            <input type="text" id="total_net"
                                class="form-control @error('total_net') is-invalid @enderror format-ribuan"
                                value="{{ (int) $data['monthly_closing']->total_profit }}" data-target="total_net_raw">
                            <span class="input-group-text">.00</span>
                            @error('total_net')
                                <small class="invalid-feedback">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>
                        <input type="hidden" name="total_net" id="total_net_raw">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Threshold Profit (opsional)</label>
                        <div class="input-group mb-3">
                            <span class="input-group-text">Rp.</span>
                            <input type="text" id="threshold_profit" class="form-control format-ribuan"
                                placeholder="Kosongkan untuk auto" data-target="threshold_profit_raw" value="" disabled>
                            <span class="input-group-text">.00</span>
                        </div>
                    </div>
                </div>
                <div class="row">

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Catatan (opsional)</label>
                        <textarea name="notes" rows="2" class="form-control"></textarea>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary">Tutup Buku & Prediksi Bulan Depan</button>
                        <a href="{{ route('owner.predictions.index') }}" class="btn btn-outline-secondary">Lihat
                            Prediksi</a>
                    </div>
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

            var profitInput = $('#total_net');
            var rawProfitInputId = profitInput.data('target');
            var rawProfitInput = $('#' + rawProfitInputId);
            var profitValue = profitInput.val();
            var cleanedProfitValue = profitValue.replace(/[^\d]/g, '');
            rawProfitInput.val(cleanedProfitValue);
            profitInput.val(formatAngkaRibuan(profitInput.val()))


            $('.format-ribuan').on('input', function(e) {
                var input = $(this);
                var rawValueInputId = input.data('target');
                var rawValueInput = $('#' + rawValueInputId);

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

                // Sesuaikan posisi kursor
                input[0].setSelectionRange(
                    cursorPos + (newLength - oldLength),
                    cursorPos + (newLength - oldLength)
                );
            });
        })
    </script>
@endpush
