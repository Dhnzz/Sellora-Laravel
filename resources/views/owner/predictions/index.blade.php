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

            <form class="row g-2" method="POST" action="{{ route('owner.predictions.predict') }}">
                @csrf
                <div class="col-auto">
                    <input type="number" step="0.01" min="0" required name="threshold_profit"
                        class="form-control" placeholder="Masukkan Threshold Profit (Rp)">
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary">Prediksi Bulan Depan</button>
                </div>
            </form>

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

        // Ambil prediksi terakhir untuk ditaruh sebagai titik di ujung
        let nextLabel = null,
            nextValue = null;
        if (preds.length > 0) {
            const last = preds[preds.length - 1];
            nextLabel = `${String(last.year).padStart(4,'0')}-${String(last.month).padStart(2,'0')}-01`;
            nextValue = Number(last.predicted_profit);
        }

        const ctx = document.getElementById('chartRevenue').getContext('2d');
        const datasets = [{
            label: 'Pendapatan Historis',
            data: dataHist,
            tension: 0.25,
            borderWidth: 2,
        }];

        if (nextLabel && nextValue !== null) {
            labels.push(nextLabel);
            datasets.push({
                label: 'Prediksi Bulan Depan',
                data: [...Array(dataHist.length).fill(null), nextValue],
                borderWidth: 2,
                pointRadius: 4,
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
