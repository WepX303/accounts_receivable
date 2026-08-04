@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="avatar-title bg-primary-subtle text-primary rounded fs-4"
                                      style="width:42px;height:42px;">
                                    <i class="{{ $type === 'received'
                                        ? 'ri-wallet-3-line'
                                        : ($type === 'expected-paid'
                                            ? 'ri-checkbox-circle-line'
                                            : 'ri-calendar-check-line') }}"></i>
                                </span>

                                <div>
                                    <h4 class="mb-0">
                                        {{ $type === 'received'
                                            ? __('pages/reports.payment_calendar.details.received_heading')
                                            : ($type === 'expected-paid'
                                                ? __('pages/reports.payment_calendar.details.expected_paid_heading')
                                                : __('pages/reports.payment_calendar.details.expected_heading')) }}
                                    </h4>

                                    <div class="text-muted">
                                        {{ __('pages/reports.common.report_date') }}:
                                        <span class="fw-semibold">{{ $date->format('d.m.Y') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('reports.payment-calendar', ['month' => $date->format('Y-m')]) }}"
                               class="btn btn-light border">
                                <i class="ri-arrow-left-line me-1"></i>
                                {{ __('pages/reports.payment_calendar.details.back_to_calendar') }}
                            </a>

                            <a href="{{ route('reports.payment-calendar.details.export', [
                                'type' => $type,
                                'date' => $date->toDateString(),
                            ]) }}"
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

    {{-- Search --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.payment-calendar.details') }}">
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="date" value="{{ $date->toDateString() }}">

                <div class="row g-3 align-items-end">
                    <div class="col-md-9">
                        <label class="form-label">
                            {{ __('pages/reports.payment_calendar.details.search_customer') }}</label>
                        <input type="text"
                               name="q"
                               class="form-control"
                               placeholder="{{ __('pages/reports.payment_calendar.details.search_placeholder') }}"
                               value="{{ $q ?? request('q') }}">
                    </div>

                    <div class="col-md-3">
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary w-100" type="submit">
                                {{ __('pages/reports.common.search') }}
                            </button>

                            <a href="{{ route('reports.payment-calendar.details', [
                                'type' => $type,
                                'date' => $date->toDateString(),
                            ]) }}"
                               class="btn btn-light border w-100">
                                {{ __('pages/reports.common.clear') }}
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if ($type === 'received')
        <div class="card">
            <div class="card-header">
                <h5 class="mb-1">{{ __('pages/reports.payment_calendar.details.received_title') }}</h5>
                <p class="text-muted mb-0">
                    {{ __('pages/reports.payment_calendar.details.received_hint') }}
                </p>
            </div>

            <div class="card-body">
                <div class="table-responsive" style="overflow-x:auto;">
                    <table class="table table-hover align-middle table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('pages/reports.payment_calendar.details.payment_id') }}</th>
                                <th>{{ __('pages/reports.common.credit_id') }}</th>
                                <th>{{ __('pages/reports.common.customer') }}</th>
                                <th>{{ __('pages/reports.common.contract') }}</th>
                                <th>{{ __('pages/reports.common.phone') }}</th>
                                <th>{{ __('pages/reports.common.branch') }}</th>
                                <th>{{ __('pages/reports.common.method') }}</th>
                                <th class="text-end">{{ __('pages/reports.payment_calendar.details.received') }}</th>
                                <th class="text-end">{{ __('pages/reports.common.change') }}</th>
                                <th class="text-end">{{ __('pages/reports.payment_calendar.details.net_applied') }}</th>
                                <th>{{ __('pages/reports.common.cashier') }}</th>
                                <th>{{ __('pages/reports.payment_calendar.details.payment_time') }}</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($rows as $p)
                                @php
                                    $received = (float) ($p->pay_amount ?? 0);
                                    $change = (float) ($p->change_amount ?? 0);
                                    $net = round($received - $change, 2);
                                @endphp

                                <tr>
                                    <td class="fw-semibold">#{{ $p->id }}</td>
                                    <td>{{ $p->credit_source_id ?? '-' }}</td>

                                    <td>
                                        <div class="fw-medium">{{ $p->customer_name ?? '-' }}</div>
                                    </td>

                                    <td>{{ $p->customer_contract ?? '-' }}</td>
                                    <td>{{ $p->customer_phone ?? '-' }}</td>
                                    <td>{{ $p->branch ?? '-' }}</td>

                                    <td>
                                        <span class="badge bg-primary-subtle text-primary">
                                            {{ strtoupper($p->method ?? '-') }}
                                        </span>
                                    </td>

                                    <td class="text-end">{{ number_format($received, 2) }} TMT</td>
                                    <td class="text-end">{{ number_format($change, 2) }} TMT</td>

                                    <td class="text-end fw-semibold text-success">
                                        {{ number_format($net, 2) }} TMT
                                    </td>

                                    <td>{{ $p->created_by_name ?? '-' }}</td>
                                    <td>{{ $p->created_at ? $p->created_at->format('d.m.Y H:i') : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center text-muted py-4">
                                        {{ __('pages/reports.payment_calendar.details.empty') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $rows->links('vendor.pagination.custom') }}
                </div>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-header">
                <h5 class="mb-1">
                    {{ $type === 'expected-paid'
                        ? __('pages/reports.payment_calendar.details.expected_paid_title')
                        : __('pages/reports.payment_calendar.details.expected_title') }}
                </h5>

                <p class="text-muted mb-0">
                    {{ $type === 'expected-paid'
                        ? __('pages/reports.payment_calendar.details.expected_paid_hint')
                        : __('pages/reports.payment_calendar.details.expected_hint') }}
                </p>
            </div>

            <div class="card-body">
                <div class="table-responsive" style="overflow-x:auto;">
                    <table class="table table-hover align-middle table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('pages/reports.common.credit_id') }}</th>
                                <th>{{ __('pages/reports.common.customer') }}</th>
                                <th>{{ __('pages/reports.common.contract') }}</th>
                                <th>{{ __('pages/reports.common.phone') }}</th>
                                <th>{{ __('pages/reports.common.branch') }}</th>
                                <th class="text-end">{{ __('pages/reports.common.total_debt') }}</th>
                                <th class="text-end">{{ __('pages/reports.payment_calendar.details.paid_total') }}</th>
                                <th class="text-end">{{ __('pages/reports.payment_calendar.details.remaining_total') }}
                                </th>
                                <th class="text-end">
                                    {{ __('pages/reports.payment_calendar.details.expected_installment') }}</th>
                                <th class="text-end">{{ __('pages/reports.payment_calendar.details.paid_today') }}</th>
                                <th class="text-end">{{ __('pages/reports.payment_calendar.details.missing_today') }}</th>
                                <th>{{ __('pages/reports.common.status') }}</th>
                                <th>{{ __('pages/reports.common.credit_date') }}</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($rows as $c)
                                @php
                                    $statusClass = match ($c->payment_status) {
                                        'paid' => 'success',
                                        'partial' => 'warning',
                                        default => 'danger',
                                    };

                                    $statusText = match ($c->payment_status) {
                                        'paid' => __('pages/reports.payment_calendar.details.status_paid'),
                                        'partial' => __('pages/reports.payment_calendar.details.status_partial'),
                                        default => __('pages/reports.payment_calendar.details.status_unpaid'),
                                    };
                                @endphp

                                <tr>
                                    <td class="fw-semibold">#{{ $c->source_id }}</td>

                                    <td>
                                        <div class="fw-medium">{{ $c->name ?? '-' }}</div>
                                        <div class="text-muted small">
                                            ClientRef: {{ $c->clientref ?? '-' }}
                                        </div>
                                    </td>

                                    <td>{{ $c->contract ?? '-' }}</td>
                                    <td>{{ $c->phone ?? '-' }}</td>
                                    <td>{{ $c->branch ?? '-' }}</td>

                                    <td class="text-end">{{ number_format($c->amount, 2) }} TMT</td>
                                    <td class="text-end">{{ number_format($c->paid, 2) }} TMT</td>

                                    <td class="text-end fw-semibold text-danger">
                                        {{ number_format($c->remaining, 2) }} TMT
                                    </td>

                                    <td class="text-end fw-semibold text-primary">
                                        {{ number_format($c->installment, 2) }} TMT
                                    </td>

                                    <td class="text-end fw-semibold text-success">
                                        {{ number_format($c->paid_today, 2) }} TMT
                                    </td>

                                    <td class="text-end fw-semibold {{ $c->missing_today > 0 ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($c->missing_today, 2) }} TMT
                                    </td>

                                    <td>
                                        <span class="badge bg-{{ $statusClass }}-subtle text-{{ $statusClass }}">
                                            {{ $statusText }}
                                        </span>
                                    </td>

                                    <td>
                                        {{ $c->date_ ? \Carbon\Carbon::parse($c->date_)->format('d.m.Y') : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" class="text-center text-muted py-4">
                                        {{ __('pages/reports.payment_calendar.details.empty') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $rows->links('vendor.pagination.custom') }}
                </div>
            </div>
        </div>
    @endif
@endsection
