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
            <h5 class="mb-3">Prediksi Pendapatan Bulanan (LSTM)</h5>

            <a href="{{ route('owner.closing.index') }}" class="btn btn-sm btn-primary mb-3"><i
                    class="ti ti-arrow-left"></i>
                Kembali</a>

            <div class="mt-4">
                <canvas id="chartRevenue"></canvas>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-body">
            <h6 class="mb-3">Hasil Prediksi Tersimpan</h6>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Periode</th>
                            <th>Prediksi (Rp)</th>
                            <th>Threshold (Rp)</th>
                            <th>Profitable?</th>
                            <th>% vs Last</th>
                            <th>Model</th>
                            <th>Cashflow Plan (ringkas)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($preds as $p)
                            <tr>
                                <td>{{ sprintf('%04d-%02d', $p->year, $p->month) }}</td>
                                <td>{{ number_format($p->predicted_profit, 2, ',', '.') }}</td>
                                <td>{{ number_format($p->threshold_profit, 2, ',', '.') }}</td>
                                <td>
                                    <span class="badge {{ $p->is_profitable ? 'bg-success' : 'bg-danger' }}">
                                        {{ $p->is_profitable ? 'Ya' : 'Tidak' }}
                                    </span>
                                </td>
                                <td>{{ $p->pct_change_vs_last !== null ? number_format($p->pct_change_vs_last, 2, ',', '.') . '%' : '-' }}
                                </td>
                                <td>{{ $p->model_version }}</td>
                                <td>
                                    @php $plan = $p->meta['cashflow_plan'] ?? null; @endphp
                                    @if ($plan)
                                        <div><small>Status: {{ $plan['status'] }}</small></div>
                                        @if (isset($plan['purchase_budget_next_month']))
                                            <div><small>Budget: Rp
                                                    {{ number_format($plan['purchase_budget_next_month'], 2, ',', '.') }}</small>
                                            </div>
                                        @endif
                                    @else
                                        -
                                    @endif
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const historical = @json($historical);
        const preds = @json($preds);

        // Build label & data historis
        const labels = historical.map(r => r.ym);
        const dataHist = historical.map(r => Number(r.total_profit));

        // Ambil semua prediksi untuk ditampilkan sebagai node prediksi
        const predictionLabels = [];
        const predictionData = [];

        if (preds.length > 0) {
            preds.forEach(pred => {
                const predLabel = `${String(pred.year).padStart(4,'0')}-${String(pred.month).padStart(2,'0')}-01`;
                predictionLabels.push(predLabel);
                predictionData.push(Number(pred.predicted_profit));
            });
        }

        const ctx = document.getElementById('chartRevenue').getContext('2d');

        // Dataset untuk data historis saja
        const datasets = [{
            label: 'Pendapatan Historis',
            data: dataHist,
            tension: 0.25,
            borderWidth: 2,
            borderColor: 'rgba(75, 192, 192, 1)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            pointRadius: 3,
            pointBackgroundColor: 'rgba(75, 192, 192, 1)',
            pointBorderColor: '#fff',
            pointBorderWidth: 1,
        }];

        if (predictionLabels.length > 0) {
            // Buat array data prediksi dengan null untuk periode yang tidak ada prediksi
            const predDataArray = [];

            labels.forEach((label, index) => {
                const predIndex = predictionLabels.indexOf(label);
                if (predIndex !== -1) {
                    // Jika ada prediksi untuk periode ini, gunakan nilai prediksi
                    predDataArray.push(predictionData[predIndex]);
                } else {
                    // Jika tidak ada prediksi, gunakan null
                    predDataArray.push(null);
                }
            });

            // Tambahkan prediksi untuk periode yang tidak ada di data historis
            predictionLabels.forEach((predLabel, index) => {
                if (!labels.includes(predLabel)) {
                    labels.push(predLabel);
                    predDataArray.push(predictionData[index]);
                }
            });

            datasets.push({
                label: 'Prediksi Pendapatan',
                data: predDataArray,
                borderWidth: 3,
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                pointRadius: 5,
                pointBackgroundColor: 'rgba(255, 99, 132, 1)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                tension: 0.2,
                fill: false,
                // Tampilkan titik untuk semua data prediksi
                pointHoverRadius: 8,
            });
        }

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        ticks: {
                            callback: (v) => 'Rp ' + Intl.NumberFormat('id-ID').format(v)
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => 'Rp ' + Intl.NumberFormat('id-ID').format(ctx.parsed.y)
                        }
                    }
                }
            }
        });
    </script>
@endpush
