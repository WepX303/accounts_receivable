@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="avatar-title bg-primary-subtle text-primary rounded fs-4" style="width:42px;height:42px;">
                                    <i class="{{ $type === 'received' ? 'ri-wallet-3-line' : 'ri-calendar-check-line' }}"></i>
                                </span>

                                <div>
                                    <h4 class="mb-0">
                                        {{ $type === 'received' ? 'Received Payments' : 'Expected Payments' }}
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

    @if ($type === 'received')
        <div class="card">
            <div class="card-header">
                <h5 class="mb-1">Customers who paid on this day</h5>
                <p class="text-muted mb-0">All non-voided payments recorded for the selected date.</p>
            </div>

            <div class="card-body">
                <div class="table-responsive">
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
                                    $received = (float) $p->pay_amount;
                                    $change = (float) ($p->change_amount ?? 0);
                                    $net = $received - $change;
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
                <h5 class="mb-1">Customers expected to pay on this day</h5>
                <p class="text-muted mb-0">Installment plan is calculated from credit date and total debt divided by 6.</p>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Credit ID</th>
                                <th>Customer</th>
                                <th>Contract</th>
                                <th>Phone</th>
                                <th>Branch</th>
                                <th class="text-end">Total Debt</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Remaining</th>
                                <th class="text-end">Expected Installment</th>
                                <th>Credit Date</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($rows as $c)
                                @php
                                    $amount = (float) ($c->amount_local ?? ($c->amount ?? 0));
                                    $paid = (float) ($c->paid_local ?? ($c->paid ?? 0));
                                    $remaining = max($amount - $paid, 0);
                                    $installment = round($amount / 6, 2);
                                @endphp

                                <tr>
                                    <td class="fw-semibold">#{{ $c->source_id }}</td>
                                    <td>
                                        <div class="fw-medium">{{ $c->name ?? '-' }}</div>
                                    </td>
                                    <td>{{ $c->contract ?? '-' }}</td>
                                    <td>{{ $c->phone ?? '-' }}</td>
                                    <td>{{ $c->branch ?? '-' }}</td>
                                    <td class="text-end">{{ number_format($amount, 2) }} TMT</td>
                                    <td class="text-end">{{ number_format($paid, 2) }} TMT</td>
                                    <td class="text-end fw-semibold text-danger">
                                        {{ number_format($remaining, 2) }} TMT
                                    </td>
                                    <td class="text-end fw-semibold text-primary">
                                        {{ number_format($installment, 2) }} TMT
                                    </td>
                                    <td>{{ $c->date_ ? $c->date_->format('d.m.Y') : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
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