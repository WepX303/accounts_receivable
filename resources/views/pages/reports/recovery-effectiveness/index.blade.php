@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        <div>
                            <h4 class="mb-1">{{ __('pages/reports.recovery_effectiveness.title') }}</h4>
                            <p class="text-muted mb-0">
                                {{ __('pages/reports.recovery_effectiveness.subtitle') }}
                            </p>
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <div class="text-muted me-2">
                                {{ __('pages/reports.common.period') }}:
                                <span class="fw-semibold">
                                    {{ $dateFrom->format('d.m.Y') }} - {{ $dateTo->format('d.m.Y') }}
                                </span>
                            </div>

                            <a href="{{ route('reports.recovery-effectiveness.export', request()->query()) }}"
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
                    <form method="GET" action="{{ route('reports.recovery-effectiveness') }}">
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
                                <label
                                    class="form-label">{{ __('pages/reports.recovery_effectiveness.min_recovered') }}</label>
                                <input type="number" min="0" step="0.01" name="min_recovered"
                                    class="form-control" value="{{ $minRecovered }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">{{ __('pages/reports.common.sort') }}</label>
                                <select name="sort" class="form-select">
                                    <option value="recovered_desc" {{ $sort === 'recovered_desc' ? 'selected' : '' }}>
                                        {{ __('pages/reports.recovery_effectiveness.sort_recovered_desc') }}</option>
                                    <option value="recovered_asc" {{ $sort === 'recovered_asc' ? 'selected' : '' }}>
                                        {{ __('pages/reports.recovery_effectiveness.sort_recovered_asc') }}</option>
                                    <option value="rate_desc" {{ $sort === 'rate_desc' ? 'selected' : '' }}>
                                        {{ __('pages/reports.recovery_effectiveness.sort_rate_desc') }}</option>
                                    <option value="rate_asc" {{ $sort === 'rate_asc' ? 'selected' : '' }}>
                                        {{ __('pages/reports.recovery_effectiveness.sort_rate_asc') }}</option>
                                    <option value="remaining_desc" {{ $sort === 'remaining_desc' ? 'selected' : '' }}>
                                        {{ __('pages/reports.recovery_effectiveness.sort_remaining_desc') }}</option>
                                    <option value="customer_asc" {{ $sort === 'customer_asc' ? 'selected' : '' }}>
                                        {{ __('pages/reports.common.customer_asc') }}</option>
                                    <option value="branch_asc" {{ $sort === 'branch_asc' ? 'selected' : '' }}>
                                        {{ __('pages/reports.common.branch_asc') }}</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">{{ __('pages/reports.common.search') }}</label>
                                <input type="text" name="q" class="form-control"
                                    placeholder="{{ __('pages/reports.recovery_effectiveness.search_placeholder') }}"
                                    value="{{ $q }}">
                            </div>

                            <div class="col-md-12">
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn btn-primary" type="submit">
                                        {{ __('pages/reports.common.apply') }}
                                    </button>

                                    <a href="{{ route('reports.recovery-effectiveness') }}" class="btn btn-light border">
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

    {{-- Main Summary --}}
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.recovery_effectiveness.overdue_customers') }}</p>
                    <h4 class="mb-0">{{ number_format($summary['overdue_customers']) }}</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100 border-success">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.recovery_effectiveness.recovered_customers') }}</p>
                    <h4 class="mb-0 text-success">{{ number_format($summary['recovered_customers']) }}</h4>
                    <p class="text-muted mt-2 mb-0">
                        {{ number_format($summary['customer_recovery_rate'], 2) }}%
                    </p>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100 border-danger">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.recovery_effectiveness.not_recovered') }}</p>
                    <h4 class="mb-0 text-danger">{{ number_format($summary['not_recovered_customers']) }}</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100 border-primary">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.recovery_effectiveness.customer_recovery_rate') }}</p>
                    <h4 class="mb-0 text-primary">{{ number_format($summary['customer_recovery_rate'], 2) }}%</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mt-1">
        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">{{ __('pages/reports.common.total_debt') }}</p>
                    <h4 class="mb-0">{{ number_format($summary['total_debt'], 2) }} TMT</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100 border-success">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.recovery_effectiveness.net_recovered') }}</p>
                    <h4 class="mb-0 text-success">{{ number_format($summary['net_recovered'], 2) }} TMT</h4>
                    <p class="text-muted mt-2 mb-0">
                        {{ number_format($summary['amount_recovery_rate'], 2) }}%
                    </p>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.recovery_effectiveness.gross_recovered') }}</p>
                    <h4 class="mb-0">{{ number_format($summary['gross_recovered'], 2) }} TMT</h4>
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

    {{-- Branch Recovery --}}
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('pages/reports.recovery_effectiveness.branch_table_title') }}</h5>
                </div>

                <div class="card-body">
                    <div class="table-responsive" style="overflow-x:auto;">
                        <table class="table table-hover align-middle mb-0 text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('pages/reports.common.branch') }}</th>
                                    <th class="text-end">{{ __('pages/reports.common.customers') }}</th>
                                    <th class="text-end">{{ __('pages/reports.recovery_effectiveness.recovered') }}</th>
                                    <th class="text-end">{{ __('pages/reports.recovery_effectiveness.customer_rate') }}
                                    </th>
                                    <th class="text-end">{{ __('pages/reports.common.total_debt') }}</th>
                                    <th class="text-end">{{ __('pages/reports.recovery_effectiveness.net_recovered') }}
                                    </th>
                                    <th class="text-end">{{ __('pages/reports.recovery_effectiveness.amount_rate') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($branchRows as $row)
                                    <tr>
                                        <td class="fw-semibold">{{ $row->branch_name }}</td>
                                        <td class="text-end">{{ number_format($row->customers) }}</td>
                                        <td class="text-end text-success fw-semibold">
                                            {{ number_format($row->recovered_customers) }}
                                        </td>
                                        <td class="text-end">{{ number_format($row->customer_recovery_rate, 2) }}%</td>
                                        <td class="text-end">{{ number_format($row->total_debt, 2) }} TMT</td>
                                        <td class="text-end fw-semibold text-success">
                                            {{ number_format($row->net_recovered, 2) }} TMT
                                        </td>
                                        <td class="text-end">{{ number_format($row->amount_recovery_rate, 2) }}%</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            {{ __('pages/reports.recovery_effectiveness.no_branch_data') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            <tfoot class="table-light">
                                <tr>
                                    <th>{{ __('pages/reports.common.total') }}</th>
                                    <th class="text-end">{{ number_format($summary['overdue_customers']) }}</th>
                                    <th class="text-end">{{ number_format($summary['recovered_customers']) }}</th>
                                    <th class="text-end">{{ number_format($summary['customer_recovery_rate'], 2) }}%</th>
                                    <th class="text-end">{{ number_format($summary['total_debt'], 2) }} TMT</th>
                                    <th class="text-end">{{ number_format($summary['net_recovered'], 2) }} TMT</th>
                                    <th class="text-end">{{ number_format($summary['amount_recovery_rate'], 2) }}%</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Customer Details --}}
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex flex-column flex-lg-row justify-content-between gap-2">
                    <div>
                        <h5 class="mb-0">{{ __('pages/reports.recovery_effectiveness.customer_table_title') }}</h5>
                        <div class="text-muted small mt-1">
                            {{ __('pages/reports.recovery_effectiveness.customer_table_hint') }}
                        </div>
                    </div>

                    <div class="text-muted small">
                        {{ __('pages/reports.common.total') }}: {{ number_format($summary['overdue_customers']) }}
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive" style="overflow-x:auto;">
                        <table class="table table-hover align-middle mb-0 text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('pages/reports.common.credit_id') }}</th>
                                    <th>{{ __('pages/reports.common.customer') }}</th>
                                    <th>{{ __('pages/reports.common.contract') }}</th>
                                    <th>{{ __('pages/reports.common.phone') }}</th>
                                    <th>{{ __('pages/reports.common.branch') }}</th>
                                    <th class="text-end">{{ __('pages/reports.common.total_debt') }}</th>
                                    <th class="text-end">{{ __('pages/reports.common.paid') }}</th>
                                    <th class="text-end">{{ __('pages/reports.common.remaining') }}</th>
                                    <th class="text-end">{{ __('pages/reports.common.payments') }}</th>
                                    <th class="text-end">{{ __('pages/reports.recovery_effectiveness.gross_recovered') }}
                                    </th>
                                    <th class="text-end">{{ __('pages/reports.common.change') }}</th>
                                    <th class="text-end">{{ __('pages/reports.recovery_effectiveness.net_recovered') }}
                                    </th>
                                    <th class="text-end">
                                        {{ __('pages/reports.recovery_effectiveness.recovery_percent') }}</th>
                                    <th>{{ __('pages/reports.common.last_payment') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($rows as $row)
                                    <tr>
                                        <td>{{ $row->source_id }}</td>

                                        <td>
                                            <div class="fw-semibold">{{ $row->name ?? '-' }}</div>
                                            <div class="text-muted small">
                                                ClientRef: {{ $row->clientref ?? '-' }}
                                            </div>
                                        </td>

                                        <td>{{ $row->contract ?? '-' }}</td>
                                        <td>{{ $row->phone ?? '-' }}</td>
                                        <td>{{ $row->branch ?? '-' }}</td>

                                        <td class="text-end">{{ number_format($row->total, 2) }} TMT</td>
                                        <td class="text-end">{{ number_format($row->paid, 2) }} TMT</td>
                                        <td class="text-end text-danger fw-semibold">
                                            {{ number_format($row->remaining, 2) }} TMT
                                        </td>

                                        <td class="text-end">{{ number_format($row->payment_count) }}</td>
                                        <td class="text-end">{{ number_format($row->gross_recovered, 2) }} TMT</td>
                                        <td class="text-end">{{ number_format($row->change_total, 2) }} TMT</td>
                                        <td class="text-end fw-semibold text-success">
                                            {{ number_format($row->net_recovered, 2) }} TMT
                                        </td>
                                        <td class="text-end">
                                            {{ number_format($row->recovery_rate, 2) }}%
                                        </td>

                                        <td>
                                            {{ $row->last_payment_at ? $row->last_payment_at->format('d.m.Y H:i') : '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="14" class="text-center text-muted py-4">
                                            {{ __('pages/reports.recovery_effectiveness.empty') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="5">{{ __('pages/reports.common.total') }}</th>
                                    <th class="text-end">{{ number_format($summary['total_debt'], 2) }} TMT</th>
                                    <th></th>
                                    <th class="text-end">{{ number_format($summary['total_remaining'], 2) }} TMT</th>
                                    <th></th>
                                    <th class="text-end">{{ number_format($summary['gross_recovered'], 2) }} TMT</th>
                                    <th class="text-end">{{ number_format($summary['change_total'], 2) }} TMT</th>
                                    <th class="text-end">{{ number_format($summary['net_recovered'], 2) }} TMT</th>
                                    <th class="text-end">{{ number_format($summary['amount_recovery_rate'], 2) }}%</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $rows->links('vendor.pagination.custom') }}
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
