@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        <div>
                            <h4 class="mb-1">{{ __('pages/reports.collection_trend.title') }}</h4>
                            <p class="text-muted mb-0">
                                {{ __('pages/reports.collection_trend.subtitle') }}
                            </p>
                        </div>

                        <div class="text-muted">
                            {{ __('pages/reports.common.period') }}:
                            <span class="fw-semibold">
                                {{ $dateFrom->format('d.m.Y') }} - {{ $dateTo->format('d.m.Y') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('reports.collection-trend') }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label">{{ __('pages/reports.common.date_from') }}</label>
                                <input type="date" name="date_from" class="form-control"
                                    value="{{ request('date_from', $dateFrom->format('Y-m-d')) }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">{{ __('pages/reports.common.date_to') }}</label>
                                <input type="date" name="date_to" class="form-control"
                                    value="{{ request('date_to', $dateTo->format('Y-m-d')) }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">{{ __('pages/reports.collection_trend.group_by') }}</label>
                                <select name="group_by" class="form-select">
                                    <option value="day" {{ $groupBy === 'day' ? 'selected' : '' }}>
                                        {{ __('pages/reports.collection_trend.daily') }}</option>
                                    <option value="week" {{ $groupBy === 'week' ? 'selected' : '' }}>
                                        {{ __('pages/reports.collection_trend.weekly') }}</option>
                                    <option value="month" {{ $groupBy === 'month' ? 'selected' : '' }}>
                                        {{ __('pages/reports.collection_trend.monthly') }}</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">{{ __('pages/reports.common.branch') }}</label>
                                <select name="branch" class="form-select">
                                    <option value="">{{ __('pages/reports.common.all_branches') }}</option>
                                    @foreach ($branches as $b)
                                        <option value="{{ $b }}" {{ $branch === $b ? 'selected' : '' }}>
                                            {{ $b }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">{{ __('pages/reports.common.cashier') }}</label>
                                <select name="cashier" class="form-select">
                                    <option value="">{{ __('pages/reports.common.all_cashiers') }}</option>
                                    @foreach ($cashiers as $c)
                                        <option value="{{ $c }}" {{ $cashier === $c ? 'selected' : '' }}>
                                            {{ $c }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-primary w-100" type="submit">
                                        {{ __('pages/reports.common.apply') }}
                                    </button>

                                    <a href="{{ route('reports.collection-trend') }}" class="btn btn-light border w-100">
                                        {{ __('pages/reports.common.clear') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary --}}
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">{{ __('pages/reports.common.net_collection') }}</p>
                    <h4 class="mb-0 text-success">{{ number_format($summary['net_total'], 2) }} TMT</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">{{ __('pages/reports.common.transactions') }}</p>
                    <h4 class="mb-0">{{ number_format($summary['tx_count']) }}</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.collection_trend.average_net') }}</p>
                    <h4 class="mb-0">{{ number_format($summary['avg_net'], 2) }} TMT</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">{{ __('pages/reports.common.change_returned') }}</p>
                    <h4 class="mb-0">{{ number_format($summary['change_total'], 2) }} TMT</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-3 row-cols-xl-3 g-3 mt-1">
        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">{{ __('pages/reports.common.cash') }}</p>
                    <h4 class="mb-0">{{ number_format($summary['cash_total'], 2) }} TMT</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">{{ __('pages/reports.common.card') }}</p>
                    <h4 class="mb-0">{{ number_format($summary['card_total'], 2) }} TMT</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">{{ __('pages/reports.common.phone') }}</p>
                    <h4 class="mb-0">{{ number_format($summary['phone_total'], 2) }} TMT</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- Best / Lowest --}}
    <div class="row mt-3">
        <div class="col-xl-6">
            <div class="card border-success">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.collection_trend.best_period') }}</p>
                    <h5 class="mb-1">
                        {{ $bestRow?->period_key ?? '-' }}
                    </h5>
                    <h4 class="mb-0 text-success">
                        {{ number_format((float) ($bestRow->net_total ?? 0), 2) }} TMT
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card border-warning">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.collection_trend.lowest_period') }}</p>
                    <h5 class="mb-1">
                        {{ $lowestRow?->period_key ?? '-' }}
                    </h5>
                    <h4 class="mb-0 text-warning">
                        {{ number_format((float) ($lowestRow->net_total ?? 0), 2) }} TMT
                    </h4>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart --}}
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('pages/reports.collection_trend.chart_title') }}</h5>
                </div>

                <div class="card-body">
                    <div id="collection-trend-chart" class="apex-charts"
                        data-colors='["--vz-primary", "--vz-success", "--vz-warning"]' dir="ltr"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('pages/reports.collection_trend.table_title') }}</h5>
                </div>

                <div class="card-body">
                    <div class="table-responsive" style="overflow-x:auto;">
                        <table class="table table-hover align-middle mb-0 text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('pages/reports.collection_trend.period_column') }}</th>
                                    <th class="text-end">{{ __('pages/reports.common.transactions') }}</th>
                                    <th class="text-end">{{ __('pages/reports.common.gross') }}</th>
                                    <th class="text-end">{{ __('pages/reports.common.change') }}</th>
                                    <th class="text-end">{{ __('pages/reports.common.net') }}</th>
                                    <th class="text-end">{{ __('pages/reports.common.cash') }}</th>
                                    <th class="text-end">{{ __('pages/reports.common.card') }}</th>
                                    <th class="text-end">{{ __('pages/reports.common.phone') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($trendRows as $row)
                                    <tr>
                                        <td class="fw-semibold">{{ $row->period_key }}</td>
                                        <td class="text-end">{{ number_format((int) $row->tx_count) }}</td>
                                        <td class="text-end">{{ number_format((float) $row->gross_total, 2) }} TMT</td>
                                        <td class="text-end">{{ number_format((float) $row->change_total, 2) }} TMT</td>
                                        <td class="text-end fw-semibold text-success">
                                            {{ number_format((float) $row->net_total, 2) }} TMT
                                        </td>
                                        <td class="text-end">{{ number_format((float) $row->cash_total, 2) }} TMT</td>
                                        <td class="text-end">{{ number_format((float) $row->card_total, 2) }} TMT</td>
                                        <td class="text-end">{{ number_format((float) $row->phone_total, 2) }} TMT</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            {{ __('pages/reports.collection_trend.empty') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            <tfoot class="table-light">
                                <tr>
                                    <th>{{ __('pages/reports.common.total') }}</th>
                                    <th class="text-end">{{ number_format($summary['tx_count']) }}</th>
                                    <th class="text-end">{{ number_format($summary['gross_total'], 2) }} TMT</th>
                                    <th class="text-end">{{ number_format($summary['change_total'], 2) }} TMT</th>
                                    <th class="text-end">{{ number_format($summary['net_total'], 2) }} TMT</th>
                                    <th class="text-end">{{ number_format($summary['cash_total'], 2) }} TMT</th>
                                    <th class="text-end">{{ number_format($summary['card_total'], 2) }} TMT</th>
                                    <th class="text-end">{{ number_format($summary['phone_total'], 2) }} TMT</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        window.COLLECTION_TREND = {!! json_encode($chart) !!};
        window.COLLECTION_TREND_LABELS = {!! json_encode([
            'net' => __('pages/reports.common.net'),
            'gross' => __('pages/reports.common.gross'),
            'cash' => __('pages/reports.common.cash'),
            'card' => __('pages/reports.common.card'),
            'phone' => __('pages/reports.common.phone'),
        ]) !!};
    </script>

    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const el = document.querySelector("#collection-trend-chart");

            if (!el || typeof ApexCharts === 'undefined') {
                return;
            }

            const data = window.COLLECTION_TREND || {
                labels: [],
                net: [],
                gross: [],
                cash: [],
                card: [],
                phone: []
            };

            const t = window.COLLECTION_TREND_LABELS || {
                net: 'Net',
                gross: 'Gross',
                cash: 'Cash',
                card: 'Card',
                phone: 'Phone'
            };

            const options = {
                chart: {
                    height: 360,
                    type: 'line',
                    toolbar: {
                        show: true
                    }
                },
                series: [{
                        name: t.net,
                        data: data.net || []
                    },
                    {
                        name: t.gross,
                        data: data.gross || []
                    },
                    {
                        name: t.cash,
                        data: data.cash || []
                    },
                    {
                        name: t.card,
                        data: data.card || []
                    },
                    {
                        name: t.phone,
                        data: data.phone || []
                    }
                ],
                xaxis: {
                    categories: data.labels || []
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                dataLabels: {
                    enabled: false
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function(val) {
                            return Number(val || 0).toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }) + ' TMT';
                        }
                    }
                }
            };

            const chart = new ApexCharts(el, options);
            chart.render();
        });
    </script>
@endsection
