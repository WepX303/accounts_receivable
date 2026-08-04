@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        <div>
                            <h4 class="mb-1">{{ __('pages/reports.void_correction.title') }}</h4>
                            <p class="text-muted mb-0">
                                {{ __('pages/reports.void_correction.subtitle') }}
                            </p>
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <div class="text-muted me-2">
                                {{ __('pages/reports.common.period') }}:
                                <span class="fw-semibold">
                                    {{ $dateFrom->format('d.m.Y') }} - {{ $dateTo->format('d.m.Y') }}
                                </span>
                            </div>

                            <a href="{{ route('reports.void-correction.export', request()->query()) }}"
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
                    <form method="GET" action="{{ route('reports.void-correction') }}">
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
                                <label class="form-label">{{ __('pages/reports.common.type') }}</label>
                                <select name="type" class="form-select">
                                    <option value="all" {{ $type === 'all' ? 'selected' : '' }}>
                                        {{ __('pages/reports.common.all') }}
                                    </option>
                                    <option value="void" {{ $type === 'void' ? 'selected' : '' }}>
                                        {{ __('pages/reports.void_correction.type_void') }}
                                    </option>
                                    <option value="correction" {{ $type === 'correction' ? 'selected' : '' }}>
                                        {{ __('pages/reports.void_correction.type_correction') }}
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">{{ __('pages/reports.void_correction.actor') }}</label>
                                <select name="actor" class="form-select">
                                    <option value="">{{ __('pages/reports.void_correction.all_actors') }}</option>
                                    @foreach ($actors as $a)
                                        <option value="{{ $a }}" {{ $actor === $a ? 'selected' : '' }}>
                                            {{ $a }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
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

                            <div class="col-md-3">
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

                            <div class="col-md-5">
                                <label class="form-label">{{ __('pages/reports.common.search') }}</label>
                                <input type="text" name="q" class="form-control" value="{{ $q }}"
                                    placeholder="{{ __('pages/reports.void_correction.search_placeholder') }}">
                            </div>

                            <div class="col-md-4">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-primary w-100" type="submit">
                                        {{ __('pages/reports.common.apply') }}
                                    </button>

                                    <a href="{{ route('reports.void-correction') }}" class="btn btn-light border w-100">
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
            <div class="card card-height-100 border-danger">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.void_correction.void_count') }}
                    </p>
                    <h4 class="mb-0 text-danger">{{ number_format($summary['void_count']) }}</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100 border-danger">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.void_correction.void_amount') }}
                    </p>
                    <h4 class="mb-0 text-danger">{{ number_format($summary['void_amount'], 2) }} TMT</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.void_correction.correction_count') }}
                    </p>
                    <h4 class="mb-0">{{ number_format($summary['correction_count']) }}</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.void_correction.correction_delta') }}
                    </p>
                    <h4 class="mb-0 {{ $summary['correction_delta'] < 0 ? 'text-danger' : 'text-success' }}">
                        {{ number_format($summary['correction_delta'], 2) }} TMT
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 g-3 mt-1">
        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.void_correction.period_applied') }}
                    </p>
                    <h4 class="mb-0">{{ number_format($summary['period_applied'], 2) }} TMT</h4>
                    <p class="text-muted small mb-0 mt-1">
                        {{ __('pages/reports.void_correction.period_applied_hint') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100 {{ $summary['void_rate'] >= 5 ? 'border-danger' : '' }}">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">
                        {{ __('pages/reports.void_correction.void_rate') }}
                    </p>
                    <h4 class="mb-0 {{ $summary['void_rate'] >= 5 ? 'text-danger' : '' }}">
                        {{ number_format($summary['void_rate'], 2) }} %
                    </h4>
                    <p class="text-muted small mb-0 mt-1">
                        {{ __('pages/reports.void_correction.void_rate_hint') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Per actor --}}
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-1">{{ __('pages/reports.void_correction.actor_table_title') }}</h5>
                    <p class="text-muted mb-0">{{ __('pages/reports.void_correction.actor_table_hint') }}</p>
                </div>

                <div class="card-body">
                    <div class="table-responsive" style="overflow-x:auto;">
                        <table class="table table-hover align-middle mb-0 text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('pages/reports.void_correction.actor') }}</th>
                                    <th class="text-end">{{ __('pages/reports.void_correction.void_count') }}</th>
                                    <th class="text-end">{{ __('pages/reports.void_correction.void_amount') }}</th>
                                    <th class="text-end">{{ __('pages/reports.void_correction.correction_count') }}</th>
                                    <th class="text-end">{{ __('pages/reports.void_correction.correction_delta') }}</th>
                                    <th class="text-end">{{ __('pages/reports.void_correction.net_effect') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($actorRows as $row)
                                    <tr>
                                        <td class="fw-semibold">{{ $row['actor_name'] }}</td>
                                        <td class="text-end">{{ number_format($row['void_count']) }}</td>
                                        <td class="text-end text-danger">
                                            {{ number_format($row['void_amount'], 2) }} TMT
                                        </td>
                                        <td class="text-end">{{ number_format($row['correction_count']) }}</td>
                                        <td class="text-end">
                                            {{ number_format($row['correction_delta'], 2) }} TMT
                                        </td>
                                        <td class="text-end fw-semibold {{ $row['net_effect'] < 0 ? 'text-danger' : '' }}">
                                            {{ number_format($row['net_effect'], 2) }} TMT
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            {{ __('pages/reports.void_correction.empty') }}
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

    {{-- Events --}}
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex flex-column flex-lg-row justify-content-between gap-2">
                    <div>
                        <h5 class="mb-1">{{ __('pages/reports.void_correction.table_title') }}</h5>
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
                                    <th>{{ __('pages/reports.void_correction.event_at') }}</th>
                                    <th>{{ __('pages/reports.common.type') }}</th>
                                    <th>{{ __('pages/reports.export.payment_id') }}</th>
                                    <th>{{ __('pages/reports.common.customer') }}</th>
                                    <th>{{ __('pages/reports.common.branch') }}</th>
                                    <th>{{ __('pages/reports.common.cashier') }}</th>
                                    <th class="text-end">{{ __('pages/reports.void_correction.old_amount') }}</th>
                                    <th class="text-end">{{ __('pages/reports.void_correction.new_amount') }}</th>
                                    <th class="text-end">{{ __('pages/reports.void_correction.delta') }}</th>
                                    <th>{{ __('pages/reports.void_correction.actor') }}</th>
                                    <th>{{ __('pages/reports.void_correction.reason') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($rows as $row)
                                    <tr>
                                        <td class="fw-semibold">
                                            {{ $row['event_at'] ? \Carbon\Carbon::parse($row['event_at'])->format('d.m.Y H:i') : '-' }}
                                        </td>

                                        <td>
                                            @if ($row['type'] === 'void')
                                                <span class="badge bg-danger-subtle text-danger">
                                                    {{ __('pages/reports.void_correction.type_void') }}
                                                </span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning">
                                                    {{ __('pages/reports.void_correction.type_correction') }}
                                                </span>
                                            @endif
                                        </td>

                                        <td>
                                            #{{ $row['payment_id'] }}
                                            @if ($row['related_payment_id'])
                                                <span class="text-muted small">
                                                    &larr; #{{ $row['related_payment_id'] }}
                                                </span>
                                            @endif
                                        </td>

                                        <td>
                                            <div class="fw-semibold">{{ $row['customer_name'] ?: '-' }}</div>
                                            <div class="text-muted small">
                                                {{ $row['customer_contract'] ?: '-' }}
                                            </div>
                                        </td>

                                        <td>{{ $row['branch'] ?: '-' }}</td>
                                        <td>{{ $row['cashier'] ?: '-' }}</td>

                                        <td class="text-end">{{ number_format($row['old_amount'], 2) }} TMT</td>
                                        <td class="text-end">{{ number_format($row['new_amount'], 2) }} TMT</td>
                                        <td class="text-end fw-semibold {{ $row['delta'] < 0 ? 'text-danger' : 'text-success' }}">
                                            {{ number_format($row['delta'], 2) }} TMT
                                        </td>

                                        <td>{{ $row['actor_name'] ?: '-' }}</td>
                                        <td class="text-wrap" style="max-width:280px;">
                                            {{ $row['reason'] ?: '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center text-muted py-4">
                                            {{ __('pages/reports.void_correction.empty') }}
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
