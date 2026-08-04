@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        <div>
                            <h4 class="mb-1">{{ __('pages/reports.balance_integrity.title') }}</h4>
                            <p class="text-muted mb-0">
                                {{ __('pages/reports.balance_integrity.subtitle') }}
                            </p>
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-2">
                            @if ($dateFrom && $dateTo)
                                <div class="text-muted me-2">
                                    {{ __('pages/reports.common.period') }}:
                                    <span class="fw-semibold">
                                        {{ $dateFrom->format('d.m.Y') }} - {{ $dateTo->format('d.m.Y') }}
                                    </span>
                                </div>
                            @endif

                            <a href="{{ route('reports.balance-integrity.export', request()->query()) }}"
                                class="btn btn-success">
                                <i class="ri-file-excel-2-line me-1"></i>
                                {{ __('pages/reports.common.export_excel') }}
                            </a>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3 mb-0">
                        <i class="ri-information-line me-1"></i>
                        {{ __('pages/reports.balance_integrity.formula_hint') }}
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
                    <form method="GET" action="{{ route('reports.balance-integrity') }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label">{{ __('pages/reports.common.date_from') }}</label>
                                <input type="date" name="date_from" class="form-control"
                                    value="{{ request('date_from') }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">{{ __('pages/reports.common.date_to') }}</label>
                                <input type="date" name="date_to" class="form-control"
                                    value="{{ request('date_to') }}">
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
                                <label class="form-label">{{ __('pages/reports.balance_integrity.min_diff') }}</label>
                                <input type="number" step="0.01" min="0" name="min_diff" class="form-control"
                                    value="{{ $minDiff }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">{{ __('pages/reports.common.sort') }}</label>
                                <select name="sort" class="form-select">
                                    <option value="diff_desc" {{ $sort === 'diff_desc' ? 'selected' : '' }}>
                                        {{ __('pages/reports.balance_integrity.sort_diff_desc') }}
                                    </option>
                                    <option value="diff_asc" {{ $sort === 'diff_asc' ? 'selected' : '' }}>
                                        {{ __('pages/reports.balance_integrity.sort_diff_asc') }}
                                    </option>
                                    <option value="recent" {{ $sort === 'recent' ? 'selected' : '' }}>
                                        {{ __('pages/reports.balance_integrity.sort_recent') }}
                                    </option>
                                    <option value="customer_asc" {{ $sort === 'customer_asc' ? 'selected' : '' }}>
                                        {{ __('pages/reports.common.customer_asc') }}
                                    </option>
                                    <option value="branch_asc" {{ $sort === 'branch_asc' ? 'selected' : '' }}>
                                        {{ __('pages/reports.common.branch_asc') }}
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-5">
                                <label class="form-label">{{ __('pages/reports.common.search') }}</label>
                                <input type="text" name="q" class="form-control" value="{{ $q }}"
                                    placeholder="{{ __('pages/reports.balance_integrity.search_placeholder') }}">
                            </div>

                            <div class="col-md-3">
                                <div class="form-check mt-4">
                                    {{-- unchecked boxes are not submitted, so carry an explicit 0 --}}
                                    <input type="hidden" name="only_mismatch" value="0">
                                    <input class="form-check-input" type="checkbox" name="only_mismatch" value="1"
                                        id="onlyMismatch" {{ $onlyMismatch ? 'checked' : '' }}>
                                    <label class="form-check-label" for="onlyMismatch">
                                        {{ __('pages/reports.balance_integrity.only_mismatch') }}
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-primary w-100" type="submit">
                                        {{ __('pages/reports.common.apply') }}
                                    </button>

                                    <a href="{{ route('reports.balance-integrity') }}" class="btn btn-light border w-100">
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
                        {{ __('pages/reports.balance_integrity.checked_count') }}
                    </p>
                    <h4 class="mb-0">{{ number_format($summary['checked_count']) }}</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100 {{ $summary['mismatch_count'] > 0 ? 'border-danger' : 'border-success' }}">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.balance_integrity.mismatch_count') }}
                    </p>
                    <h4 class="mb-0 {{ $summary['mismatch_count'] > 0 ? 'text-danger' : 'text-success' }}">
                        {{ number_format($summary['mismatch_count']) }}
                    </h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.balance_integrity.over_total') }}
                    </p>
                    <h4 class="mb-0 text-success">{{ number_format($summary['over_total'], 2) }} TMT</h4>
                    <p class="text-muted small mb-0 mt-1">
                        {{ number_format($summary['over_count']) }}
                        {{ __('pages/reports.balance_integrity.records') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.balance_integrity.under_total') }}
                    </p>
                    <h4 class="mb-0 text-danger">{{ number_format($summary['under_total'], 2) }} TMT</h4>
                    <p class="text-muted small mb-0 mt-1">
                        {{ number_format($summary['under_count']) }}
                        {{ __('pages/reports.balance_integrity.records') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 g-3 mt-1">
        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.balance_integrity.net_total') }}
                    </p>
                    <h4 class="mb-0 {{ $summary['net_total'] < 0 ? 'text-danger' : '' }}">
                        {{ number_format($summary['net_total'], 2) }} TMT
                    </h4>
                    <p class="text-muted small mb-0 mt-1">
                        {{ __('pages/reports.balance_integrity.net_total_hint') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.balance_integrity.abs_total') }}
                    </p>
                    <h4 class="mb-0">{{ number_format($summary['abs_total'], 2) }} TMT</h4>
                    <p class="text-muted small mb-0 mt-1">
                        {{ __('pages/reports.balance_integrity.abs_total_hint') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Branch rollup --}}
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('pages/reports.balance_integrity.branch_table_title') }}</h5>
                </div>

                <div class="card-body">
                    <div class="table-responsive" style="overflow-x:auto;">
                        <table class="table table-hover align-middle mb-0 text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('pages/reports.common.branch') }}</th>
                                    <th class="text-end">{{ __('pages/reports.balance_integrity.credit_count') }}</th>
                                    <th class="text-end">{{ __('pages/reports.balance_integrity.net_total') }}</th>
                                    <th class="text-end">{{ __('pages/reports.balance_integrity.abs_total') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($branchRows as $row)
                                    <tr>
                                        <td class="fw-semibold">{{ $row['branch'] }}</td>
                                        <td class="text-end">{{ number_format($row['credit_count']) }}</td>
                                        <td class="text-end {{ $row['net_total'] < 0 ? 'text-danger' : '' }}">
                                            {{ number_format($row['net_total'], 2) }} TMT
                                        </td>
                                        <td class="text-end fw-semibold">
                                            {{ number_format($row['abs_total'], 2) }} TMT
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            {{ __('pages/reports.balance_integrity.no_branch_data') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Detail --}}
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex flex-column flex-lg-row justify-content-between gap-2">
                    <div>
                        <h5 class="mb-1">{{ __('pages/reports.balance_integrity.table_title') }}</h5>
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
                                    <th>{{ __('pages/reports.common.customer') }}</th>
                                    <th>{{ __('pages/reports.common.branch') }}</th>
                                    <th class="text-end">{{ __('pages/reports.export.local_amount') }}</th>
                                    <th class="text-end">{{ __('pages/reports.balance_integrity.baseline_paid') }}</th>
                                    <th class="text-end">{{ __('pages/reports.balance_integrity.applied_sum') }}</th>
                                    <th class="text-end">{{ __('pages/reports.balance_integrity.expected_paid') }}</th>
                                    <th class="text-end">{{ __('pages/reports.balance_integrity.actual_paid') }}</th>
                                    <th class="text-end">{{ __('pages/reports.balance_integrity.diff') }}</th>
                                    <th class="text-end">{{ __('pages/reports.common.payments') }}</th>
                                    <th>{{ __('pages/reports.export.paid_updated_at') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($rows as $row)
                                    <tr class="{{ $row['is_mismatch'] ? 'table-danger' : '' }}">
                                        <td>
                                            <div class="fw-semibold">{{ $row['name'] ?: '-' }}</div>
                                            <div class="text-muted small">
                                                {{ $row['contract'] ?: '-' }} &middot; #{{ $row['source_id'] }}
                                            </div>
                                        </td>

                                        <td>{{ $row['branch'] ?: '-' }}</td>

                                        <td class="text-end">{{ number_format($row['amount_local'], 2) }} TMT</td>
                                        <td class="text-end">{{ number_format($row['baseline_paid'], 2) }} TMT</td>
                                        <td class="text-end">{{ number_format($row['applied_sum'], 2) }} TMT</td>
                                        <td class="text-end">{{ number_format($row['expected_paid'], 2) }} TMT</td>
                                        <td class="text-end fw-semibold">
                                            {{ number_format($row['actual_paid'], 2) }} TMT
                                        </td>

                                        <td class="text-end fw-semibold {{ $row['diff'] < 0 ? 'text-danger' : 'text-success' }}">
                                            {{ number_format($row['diff'], 2) }} TMT
                                        </td>

                                        <td class="text-end">
                                            {{ number_format($row['active_payment_count']) }}
                                            @if ($row['voided_payment_count'] > 0)
                                                <span class="badge bg-danger-subtle text-danger ms-1">
                                                    {{ $row['voided_payment_count'] }}
                                                </span>
                                            @endif
                                        </td>

                                        <td>
                                            {{ $row['paid_updated_at'] ? \Carbon\Carbon::parse($row['paid_updated_at'])->format('d.m.Y H:i') : '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">
                                            {{ __('pages/reports.balance_integrity.empty') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
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
