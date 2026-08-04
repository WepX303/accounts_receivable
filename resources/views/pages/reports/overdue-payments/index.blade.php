@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">

                        <div>
                            <h4 class="mb-1">{{ __('pages/reports.overdue_payments.title') }}</h4>

                            <p class="text-muted mb-0">
                                {{ __('pages/reports.overdue_payments.subtitle') }}
                            </p>
                        </div>

                        <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2">

                            <div class="text-muted text-sm-end">
                                <div class="small">{{ __('pages/reports.common.report_date') }}</div>
                                <div class="fw-semibold">
                                    {{ $today->format('d.m.Y') }}
                                </div>
                            </div>
                            <a href="{{ route('reports.overdue-payments.export', request()->query()) }}"
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
                    <form method="GET" action="{{ route('reports.overdue-payments') }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-xl-3 col-md-6">
                                <label class="form-label">{{ __('pages/reports.common.search') }}</label>
                                <input type="text" name="q" class="form-control" value="{{ $q }}"
                                    placeholder="{{ __('pages/reports.overdue_payments.search_placeholder') }}">
                            </div>

                            <div class="col-xl-2 col-md-6">
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

                            <div class="col-xl-2 col-md-6">
                                <label class="form-label">{{ __('pages/reports.overdue_payments.min_overdue') }}</label>
                                <input type="number" step="0.01" min="0" name="min_overdue" class="form-control"
                                    value="{{ $minOverdue }}">
                            </div>

                            <div class="col-xl-2 col-md-6">
                                <label class="form-label">{{ __('pages/reports.overdue_payments.min_days') }}</label>
                                <input type="number" min="0" name="min_days" class="form-control"
                                    value="{{ $minDays }}">
                            </div>

                            <div class="col-xl-2 col-md-6">
                                <label class="form-label">{{ __('pages/reports.common.sort') }}</label>
                                <select name="sort" class="form-select">
                                    <option value="overdue_desc" {{ $sort === 'overdue_desc' ? 'selected' : '' }}>
                                        {{ __('pages/reports.overdue_payments.sort_overdue_desc') }}</option>
                                    <option value="overdue_asc" {{ $sort === 'overdue_asc' ? 'selected' : '' }}>
                                        {{ __('pages/reports.overdue_payments.sort_overdue_asc') }}</option>
                                    <option value="days_desc" {{ $sort === 'days_desc' ? 'selected' : '' }}>
                                        {{ __('pages/reports.overdue_payments.sort_days_desc') }}</option>
                                    <option value="days_asc" {{ $sort === 'days_asc' ? 'selected' : '' }}>
                                        {{ __('pages/reports.overdue_payments.sort_days_asc') }}</option>
                                    <option value="customer_asc" {{ $sort === 'customer_asc' ? 'selected' : '' }}>
                                        {{ __('pages/reports.common.customer_asc') }}</option>
                                    <option value="branch_asc" {{ $sort === 'branch_asc' ? 'selected' : '' }}>
                                        {{ __('pages/reports.common.branch_asc') }}</option>
                                </select>
                            </div>

                            <div class="col-xl-1 col-md-6">
                                <button type="submit" class="btn btn-primary w-100">
                                    {{ __('pages/reports.common.filter') }}
                                </button>
                            </div>
                        </div>

                        <div class="mt-3">
                            <a href="{{ route('reports.overdue-payments') }}" class="btn btn-light border btn-sm">
                                {{ __('pages/reports.common.clear_filters') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-6 g-3">
        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">{{ __('pages/reports.common.customers') }}</p>
                    <h4 class="mb-0">{{ number_format($summary['customers_count']) }}</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">{{ __('pages/reports.common.total_debt') }}</p>
                    <h4 class="mb-0">{{ number_format($summary['total_debt'], 2) }} TMT</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">{{ __('pages/reports.common.paid') }}</p>
                    <h4 class="mb-0">{{ number_format($summary['total_paid'], 2) }} TMT</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">{{ __('pages/reports.common.remaining') }}</p>
                    <h4 class="mb-0">{{ number_format($summary['total_remaining'], 2) }} TMT</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">{{ __('pages/reports.common.expected_paid') }}</p>
                    <h4 class="mb-0">{{ number_format($summary['total_expected'], 2) }} TMT</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100 border-danger">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.overdue_payments.overdue_amount') }}</p>
                    <h4 class="mb-0 text-danger">{{ number_format($summary['total_overdue'], 2) }} TMT</h4>
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
                        <h5 class="mb-1">{{ __('pages/reports.overdue_payments.table_title') }}</h5>
                        <p class="text-muted mb-0">
                            {{ __('pages/reports.common.showing', [
                                'from' => $rows->firstItem() ?? 0,
                                'to' => $rows->lastItem() ?? 0,
                                'total' => $rows->total(),
                            ]) }}
                        </p>
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
                                    <th class="text-end">{{ __('pages/reports.overdue_payments.monthly') }}</th>
                                    <th class="text-end">{{ __('pages/reports.overdue_payments.due_count') }}</th>
                                    <th class="text-end">{{ __('pages/reports.common.total_debt') }}</th>
                                    <th class="text-end">{{ __('pages/reports.common.paid') }}</th>
                                    <th class="text-end">{{ __('pages/reports.common.expected_paid') }}</th>
                                    <th class="text-end">{{ __('pages/reports.overdue_payments.overdue') }}</th>
                                    <th class="text-end">{{ __('pages/reports.common.remaining') }}</th>
                                    <th class="text-end">{{ __('pages/reports.overdue_payments.overdue_days') }}</th>
                                    <th>{{ __('pages/reports.common.credit_date') }}</th>
                                    <th>{{ __('pages/reports.overdue_payments.last_due_date') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($rows as $row)
                                    <tr>
                                        <td>{{ $row->source_id }}</td>

                                        <td>
                                            <div class="fw-semibold">{{ $row->name ?? '-' }}</div>
                                            <div class="text-muted small">ClientRef: {{ $row->clientref ?? '-' }}</div>
                                        </td>

                                        <td>{{ $row->contract ?? '-' }}</td>
                                        <td>{{ $row->phone ?? '-' }}</td>
                                        <td>{{ $row->branch ?? '-' }}</td>

                                        <td class="text-end">{{ number_format($row->monthly, 2) }} TMT</td>
                                        <td class="text-end">{{ $row->due_installment_count }} / 6</td>
                                        <td class="text-end">{{ number_format($row->total, 2) }} TMT</td>
                                        <td class="text-end">{{ number_format($row->paid, 2) }} TMT</td>
                                        <td class="text-end">{{ number_format($row->expected_paid, 2) }} TMT</td>

                                        <td class="text-end fw-semibold text-danger">
                                            {{ number_format($row->overdue_amount, 2) }} TMT
                                        </td>

                                        <td class="text-end">{{ number_format($row->remaining, 2) }} TMT</td>

                                        <td class="text-end">
                                            <span class="badge bg-danger-subtle text-danger">
                                                {{ $row->overdue_days }} {{ __('pages/reports.common.days') }}
                                            </span>
                                        </td>

                                        <td>{{ $row->credit_date?->format('d.m.Y') ?? '-' }}</td>
                                        <td>{{ $row->last_due_date?->format('d.m.Y') ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="15" class="text-center text-muted py-4">
                                            {{ __('pages/reports.overdue_payments.empty') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="7">{{ __('pages/reports.common.total') }}</th>
                                    <th class="text-end">{{ number_format($summary['total_debt'], 2) }} TMT</th>
                                    <th class="text-end">{{ number_format($summary['total_paid'], 2) }} TMT</th>
                                    <th class="text-end">{{ number_format($summary['total_expected'], 2) }} TMT</th>
                                    <th class="text-end text-danger">{{ number_format($summary['total_overdue'], 2) }} TMT
                                    </th>
                                    <th class="text-end">{{ number_format($summary['total_remaining'], 2) }} TMT</th>
                                    <th colspan="3"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted small">
                            {{ __('pages/reports.common.page_of', [
                                'current' => $rows->currentPage(),
                                'last' => $rows->lastPage(),
                            ]) }}
                        </div>

                        <div>
                            {{ $rows->onEachSide(1)->links('vendor.pagination.custom') }}
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
