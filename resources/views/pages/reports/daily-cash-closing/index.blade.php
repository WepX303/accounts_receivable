@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        <div>
                            <h4 class="mb-1">{{ __('pages/reports.daily_cash_closing.title') }}</h4>
                            <p class="text-muted mb-0">
                                {{ __('pages/reports.daily_cash_closing.subtitle') }}
                            </p>
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <div class="text-muted me-2">
                                {{ __('pages/reports.common.period') }}:
                                <span class="fw-semibold">
                                    {{ $dateFrom->format('d.m.Y') }} - {{ $dateTo->format('d.m.Y') }}
                                </span>
                            </div>

                            <a href="{{ route('reports.daily-cash-closing.export', request()->query()) }}"
                                class="btn btn-success">
                                <i class="ri-file-excel-2-line me-1"></i>
                                {{ __('pages/reports.common.export_excel') }}
                            </a>
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
                    <form method="GET" action="{{ route('reports.daily-cash-closing') }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">{{ __('pages/reports.common.date_from') }}</label>
                                <input type="date" name="date_from" class="form-control"
                                    value="{{ request('date_from', $dateFrom->format('Y-m-d')) }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">{{ __('pages/reports.common.date_to') }}</label>
                                <input type="date" name="date_to" class="form-control"
                                    value="{{ request('date_to', $dateTo->format('Y-m-d')) }}">
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

                                    <a href="{{ route('reports.daily-cash-closing') }}" class="btn btn-light border w-100">
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
                    <p class="text-muted text-uppercase fs-13 mb-2">{{ __('pages/reports.common.transactions') }}</p>
                    <h4 class="mb-0">{{ number_format($summary['tx_count']) }}</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">{{ __('pages/reports.common.gross_collection') }}</p>
                    <h4 class="mb-0">{{ number_format($summary['gross_total'], 2) }} TMT</h4>
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

        <div class="col">
            <div class="card card-height-100 border-success">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">{{ __('pages/reports.common.net_collection') }}</p>
                    <h4 class="mb-0 text-success">{{ number_format($summary['net_total'], 2) }} TMT</h4>
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

    {{-- Daily closing --}}
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('pages/reports.daily_cash_closing.table_title') }}</h5>
                </div>

                <div class="card-body">
                    <div class="table-responsive" style="overflow-x:auto;">
                        <table class="table table-hover align-middle mb-0 text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('pages/reports.common.date') }}</th>
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
                                @forelse ($dailyRows as $row)
                                    <tr>
                                        <td class="fw-semibold">
                                            {{ \Carbon\Carbon::parse($row->report_date)->format('d.m.Y') }}
                                        </td>
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
                                            {{ __('pages/reports.daily_cash_closing.empty') }}
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
