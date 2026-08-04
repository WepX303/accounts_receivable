@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        <div>
                            <h4 class="mb-1">{{ __('pages/reports.promise_to_pay.title') }}</h4>
                            <p class="text-muted mb-0">
                                {{ __('pages/reports.promise_to_pay.subtitle') }}
                            </p>
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <div class="text-muted me-2">
                                {{ __('pages/reports.common.period') }}:
                                <span class="fw-semibold">
                                    {{ $dateFrom->format('d.m.Y') }} - {{ $dateTo->format('d.m.Y') }}
                                </span>
                            </div>

                            <a href="{{ route('reports.promise-to-pay.export', request()->query()) }}"
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
                    <form method="GET" action="{{ route('reports.promise-to-pay') }}">
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
                                <label class="form-label">{{ __('pages/reports.promise_to_pay.promise_status') }}</label>
                                <select name="status" class="form-select">
                                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>
                                        {{ __('pages/reports.promise_to_pay.status.all') }}</option>
                                    <option value="broken" {{ $status === 'broken' ? 'selected' : '' }}>
                                        {{ __('pages/reports.promise_to_pay.status.broken') }}</option>
                                    <option value="today" {{ $status === 'today' ? 'selected' : '' }}>
                                        {{ __('pages/reports.promise_to_pay.status.today') }}</option>
                                    <option value="upcoming" {{ $status === 'upcoming' ? 'selected' : '' }}>
                                        {{ __('pages/reports.promise_to_pay.status.upcoming') }}</option>
                                    <option value="kept" {{ $status === 'kept' ? 'selected' : '' }}>
                                        {{ __('pages/reports.promise_to_pay.status.kept') }}</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">{{ __('pages/reports.common.search') }}</label>
                                <input type="text" name="q" class="form-control"
                                    placeholder="{{ __('pages/reports.promise_to_pay.search_placeholder') }}"
                                    value="{{ $q }}">
                            </div>

                            <div class="col-md-2">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-primary w-100" type="submit">
                                        {{ __('pages/reports.common.apply') }}
                                    </button>

                                    <a href="{{ route('reports.promise-to-pay') }}" class="btn btn-light border w-100">
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
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.promise_to_pay.promise_customers') }}</p>
                    <h4 class="mb-0">{{ number_format($summary['customers_count']) }}</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.promise_to_pay.promise_amount') }}</p>
                    <h4 class="mb-0">{{ number_format($summary['promise_amount'], 2) }} TMT</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.promise_to_pay.paid_after_promise') }}</p>
                    <h4 class="mb-0 text-success">{{ number_format($summary['paid_after_promise'], 2) }} TMT</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">{{ __('pages/reports.common.remaining') }}</p>
                    <h4 class="mb-0 text-danger">{{ number_format($summary['remaining_total'], 2) }} TMT</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mt-1">
        <div class="col">
            <div class="card card-height-100 border-danger">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.promise_to_pay.status.broken') }}</p>
                    <h4 class="mb-0 text-danger">{{ number_format($summary['broken_count']) }}</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100 border-info">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.promise_to_pay.status.today') }}</p>
                    <h4 class="mb-0 text-info">{{ number_format($summary['today_count']) }}</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100 border-primary">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.promise_to_pay.status.upcoming') }}</p>
                    <h4 class="mb-0 text-primary">{{ number_format($summary['upcoming_count']) }}</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100 border-success">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.promise_to_pay.status.kept') }}</p>
                    <h4 class="mb-0 text-success">{{ number_format($summary['kept_count']) }}</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex flex-column flex-lg-row justify-content-between gap-2">
                    <div>
                        <h5 class="mb-0">{{ __('pages/reports.promise_to_pay.table_title') }}</h5>
                        <div class="text-muted small mt-1">
                            {{ __('pages/reports.promise_to_pay.table_hint') }}
                        </div>
                    </div>

                    <div class="text-muted small">
                        {{ __('pages/reports.common.total') }}: {{ number_format($summary['customers_count']) }}
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
                                    <th>{{ __('pages/reports.promise_to_pay.promise_date') }}</th>
                                    <th class="text-end">{{ __('pages/reports.promise_to_pay.promise_amount') }}</th>
                                    <th class="text-end">{{ __('pages/reports.promise_to_pay.paid_after_promise') }}</th>
                                    <th>{{ __('pages/reports.common.status') }}</th>
                                    <th class="text-end">{{ __('pages/reports.promise_to_pay.promise_days') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($rows as $row)
                                    @php
                                        $badgeClass = match ($row->promise_status) {
                                            'today' => 'info',
                                            'upcoming' => 'primary',
                                            'kept' => 'success',
                                            'broken' => 'danger',
                                            default => 'secondary',
                                        };

                                        $statusLabel = match ($row->promise_status) {
                                            'today' => __('pages/reports.promise_to_pay.status.today'),
                                            'upcoming' => __('pages/reports.promise_to_pay.status.upcoming'),
                                            'kept' => __('pages/reports.promise_to_pay.status.kept'),
                                            'broken' => __('pages/reports.promise_to_pay.status.broken'),
                                            default => ucfirst((string) $row->promise_status),
                                        };

                                        $rowClass = match ($row->promise_status) {
                                            'today' => 'table-info',
                                            'kept' => 'table-success',
                                            'broken' => 'table-danger',
                                            default => '',
                                        };
                                    @endphp

                                    <tr class="{{ $rowClass }}">
                                        <td>{{ $row->source_id }}</td>

                                        <td>
                                            <div class="fw-semibold">{{ $row->name ?? '-' }}</div>
                                            <div class="text-muted small">ClientRef: {{ $row->clientref ?? '-' }}</div>
                                        </td>

                                        <td>{{ $row->contract ?? '-' }}</td>
                                        <td>{{ $row->phone ?? '-' }}</td>
                                        <td>{{ $row->branch ?? '-' }}</td>

                                        <td class="text-end">{{ number_format($row->total, 2) }} TMT</td>
                                        <td class="text-end">{{ number_format($row->paid, 2) }} TMT</td>
                                        <td class="text-end text-danger fw-semibold">
                                            {{ number_format($row->remaining, 2) }} TMT
                                        </td>

                                        <td>{{ $row->promise_date?->format('d.m.Y') ?? '-' }}</td>

                                        <td class="text-end">
                                            {{ number_format($row->promise_amount, 2) }} TMT
                                        </td>

                                        <td class="text-end text-success fw-semibold">
                                            {{ number_format($row->paid_after_promise, 2) }} TMT
                                        </td>

                                        <td>
                                            <span class="badge bg-{{ $badgeClass }}-subtle text-{{ $badgeClass }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </td>

                                        <td class="text-end">
                                            @if ($row->promise_status === 'broken')
                                                <span class="badge bg-danger-subtle text-danger">
                                                    {{ $row->promise_days }} {{ __('pages/reports.common.days') }}
                                                </span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="13" class="text-center text-muted py-4">
                                            {{ __('pages/reports.promise_to_pay.empty') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="5">{{ __('pages/reports.common.total') }}</th>
                                    <th></th>
                                    <th></th>
                                    <th class="text-end">{{ number_format($summary['remaining_total'], 2) }} TMT</th>
                                    <th></th>
                                    <th class="text-end">{{ number_format($summary['promise_amount'], 2) }} TMT</th>
                                    <th class="text-end">{{ number_format($summary['paid_after_promise'], 2) }} TMT</th>
                                    <th colspan="2"></th>
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
