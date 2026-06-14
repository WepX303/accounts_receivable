@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        <div>
                            <h4 class="mb-1">Collection Trend Report</h4>
                            <p class="text-muted mb-0">
                                Collection trend by day, week or month based on net received payments.
                            </p>
                        </div>

                        <div class="text-muted">
                            Period:
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
                                <label class="form-label">Date From</label>
                                <input type="date" name="date_from" class="form-control"
                                    value="{{ request('date_from', $dateFrom->format('Y-m-d')) }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Date To</label>
                                <input type="date" name="date_to" class="form-control"
                                    value="{{ request('date_to', $dateTo->format('Y-m-d')) }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Group By</label>
                                <select name="group_by" class="form-select">
                                    <option value="day" {{ $groupBy === 'day' ? 'selected' : '' }}>Daily</option>
                                    <option value="week" {{ $groupBy === 'week' ? 'selected' : '' }}>Weekly</option>
                                    <option value="month" {{ $groupBy === 'month' ? 'selected' : '' }}>Monthly</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Branch</label>
                                <select name="branch" class="form-select">
                                    <option value="">All Branches</option>
                                    @foreach ($branches as $b)
                                        <option value="{{ $b }}" {{ $branch === $b ? 'selected' : '' }}>
                                            {{ $b }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Cashier</label>
                                <select name="cashier" class="form-select">
                                    <option value="">All Cashiers</option>
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
                                        Apply
                                    </button>

                                    <a href="{{ route('reports.collection-trend') }}" class="btn btn-light border w-100">
                                        Clear
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
                    <p class="text-muted text-uppercase fs-13 mb-2">Net Collection</p>
                    <h4 class="mb-0 text-success">{{ number_format($summary['net_total'], 2) }} TMT</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Transactions</p>
                    <h4 class="mb-0">{{ number_format($summary['tx_count']) }}</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Average Net</p>
                    <h4 class="mb-0">{{ number_format($summary['avg_net'], 2) }} TMT</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Change Returned</p>
                    <h4 class="mb-0">{{ number_format($summary['change_total'], 2) }} TMT</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-3 row-cols-xl-3 g-3 mt-1">
        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Cash</p>
                    <h4 class="mb-0">{{ number_format($summary['cash_total'], 2) }} TMT</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Card</p>
                    <h4 class="mb-0">{{ number_format($summary['card_total'], 2) }} TMT</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Phone</p>
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
                    <p class="text-muted text-uppercase fs-13 mb-2">Best Collection Period</p>
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
                    <p class="text-muted text-uppercase fs-13 mb-2">Lowest Collection Period</p>
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
                    <h5 class="mb-0">Collection Trend Chart</h5>
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
                    <h5 class="mb-0">Trend Data</h5>
                </div>

                <div class="card-body">
                    <div class="table-responsive" style="overflow-x:auto;">
                        <table class="table table-hover align-middle mb-0 text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th>Period</th>
                                    <th class="text-end">Transactions</th>
                                    <th class="text-end">Gross</th>
                                    <th class="text-end">Change</th>
                                    <th class="text-end">Net</th>
                                    <th class="text-end">Cash</th>
                                    <th class="text-end">Card</th>
                                    <th class="text-end">Phone</th>
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
                                            No trend data found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            <tfoot class="table-light">
                                <tr>
                                    <th>Total</th>
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

            const options = {
                chart: {
                    height: 360,
                    type: 'line',
                    toolbar: {
                        show: true
                    }
                },
                series: [{
                        name: 'Net',
                        data: data.net || []
                    },
                    {
                        name: 'Gross',
                        data: data.gross || []
                    },
                    {
                        name: 'Cash',
                        data: data.cash || []
                    },
                    {
                        name: 'Card',
                        data: data.card || []
                    },
                    {
                        name: 'Phone',
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
