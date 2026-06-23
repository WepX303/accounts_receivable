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
                                            ? 'All Payments Received'
                                            : ($type === 'expected-paid'
                                                ? 'Paid Expected Customers'
                                                : 'Expected Payments') }}
                                    </h4>

                                    <div class="text-muted">
                                        Report Date:
                                        <span class="fw-semibold">{{ $date->format('d.m.Y') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('reports.payment-calendar', ['month' => $date->format('Y-m')]) }}"
                               class="btn btn-light border">
                                <i class="ri-arrow-left-line me-1"></i>
                                Back to Calendar
                            </a>

                            <a href="{{ route('reports.payment-calendar.details.export', [
                                'type' => $type,
                                'date' => $date->toDateString(),
                            ]) }}"
                               class="btn btn-success">
                                <i class="ri-file-excel-2-line me-1"></i>
                                Export Excel
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
                        <label class="form-label">Search Customer</label>
                        <input type="text"
                               name="q"
                               class="form-control"
                               placeholder="Customer / contract / phone / passport / branch"
                               value="{{ $q ?? request('q') }}">
                    </div>

                    <div class="col-md-3">
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary w-100" type="submit">
                                Search
                            </button>

                            <a href="{{ route('reports.payment-calendar.details', [
                                'type' => $type,
                                'date' => $date->toDateString(),
                            ]) }}"
                               class="btn btn-light border w-100">
                                Clear
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
                <h5 class="mb-1">All payments received on this day</h5>
                <p class="text-muted mb-0">
                    All non-voided payments recorded for the selected date. This list is informational and includes every payment received that day.
                </p>
            </div>

            <div class="card-body">
                <div class="table-responsive" style="overflow-x:auto;">
                    <table class="table table-hover align-middle table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Payment ID</th>
                                <th>Credit ID</th>
                                <th>Customer</th>
                                <th>Contract</th>
                                <th>Phone</th>
                                <th>Branch</th>
                                <th>Method</th>
                                <th class="text-end">Received</th>
                                <th class="text-end">Change</th>
                                <th class="text-end">Net Applied</th>
                                <th>Cashier</th>
                                <th>Payment Time</th>
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
                                        No records found.
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
                        ? 'Expected customers who paid on this day'
                        : 'Customers expected to pay on this day' }}
                </h5>

                <p class="text-muted mb-0">
                    {{ $type === 'expected-paid'
                        ? 'Only customers whose installment due date is this day and who made a payment on this day.'
                        : 'Installment plan is calculated from credit date and total debt divided by 6.' }}
                </p>
            </div>

            <div class="card-body">
                <div class="table-responsive" style="overflow-x:auto;">
                    <table class="table table-hover align-middle table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Credit ID</th>
                                <th>Customer</th>
                                <th>Contract</th>
                                <th>Phone</th>
                                <th>Branch</th>
                                <th class="text-end">Total Debt</th>
                                <th class="text-end">Paid Total</th>
                                <th class="text-end">Remaining Total</th>
                                <th class="text-end">Expected Installment</th>
                                <th class="text-end">Paid Today</th>
                                <th class="text-end">Missing Today</th>
                                <th>Status</th>
                                <th>Credit Date</th>
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
                                        'paid' => 'Paid',
                                        'partial' => 'Partial',
                                        default => 'Unpaid',
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
                                        No records found.
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