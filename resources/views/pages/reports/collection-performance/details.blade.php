@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div>
                        <h4 class="mb-1">
                            {{ $type === 'cashier' ? 'Cashier Transactions' : 'Branch Transactions' }}
                        </h4>
                        <p class="text-muted mb-0">
                            {{ $type === 'cashier' ? 'Cashier' : 'Branch' }}:
                            <span class="fw-semibold">{{ $value }}</span>
                            |
                            Period:
                            <span class="fw-semibold">
                                {{ $dateFrom->format('d.m.Y') }} - {{ $dateTo->format('d.m.Y') }}
                            </span>
                        </p>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route(
                            'reports.collection-performance',
                            collect(request()->query())->except(['type', 'value', 'page'])->toArray(),
                        ) }}"
                            class="btn btn-light border">
                            <i class="ri-arrow-left-line me-1"></i>
                            Back to Report
                        </a>
                        <a href="{{ route('reports.collection-performance.details.export', request()->query()) }}"
                            class="btn btn-success">
                            <i class="ri-file-excel-2-line me-1"></i>
                            Export Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Transactions</p>
                    <h4>{{ number_format($summary['tx_count']) }}</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Gross</p>
                    <h4>{{ number_format($summary['gross_total'], 2) }} TMT</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Change</p>
                    <h4>{{ number_format($summary['change_total'], 2) }} TMT</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card border-success">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Net</p>
                    <h4 class="text-success">{{ number_format($summary['net_total'], 2) }} TMT</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <h5 class="mb-0">Transaction List</h5>
        </div>

        <div class="card-body">
            <div class="table-responsive" style="overflow-x:auto;">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="table-light">
                        <tr>
                            <th>Payment ID</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Contract</th>
                            <th>Phone</th>
                            <th>Branch</th>
                            <th>Cashier</th>
                            <th>Method</th>
                            <th class="text-end">Received</th>
                            <th class="text-end">Change</th>
                            <th class="text-end">Net</th>
                            <th class="text-end">Cash</th>
                            <th class="text-end">Card</th>
                            <th class="text-end">Phone Amount</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($rows as $p)
                            @php
                                $net = (float) $p->pay_amount - (float) ($p->change_amount ?? 0);
                            @endphp

                            <tr>
                                <td>{{ $p->id }}</td>
                                <td>{{ $p->created_at ? $p->created_at->format('d.m.Y H:i') : '-' }}</td>
                                <td>{{ $p->customer_name ?? '-' }}</td>
                                <td>{{ $p->customer_contract ?? '-' }}</td>
                                <td>{{ $p->customer_phone ?? '-' }}</td>
                                <td>{{ $p->branch ?? '-' }}</td>
                                <td>{{ $p->created_by_name ?? '-' }}</td>
                                <td>{{ strtoupper($p->method ?? '-') }}</td>
                                <td class="text-end">{{ number_format((float) $p->pay_amount, 2) }} TMT</td>
                                <td class="text-end">{{ number_format((float) ($p->change_amount ?? 0), 2) }} TMT</td>
                                <td class="text-end fw-semibold text-success">{{ number_format($net, 2) }} TMT</td>
                                <td class="text-end">{{ number_format((float) ($p->cash_amount ?? 0), 2) }} TMT</td>
                                <td class="text-end">{{ number_format((float) ($p->card_amount ?? 0), 2) }} TMT</td>
                                <td class="text-end">{{ number_format((float) ($p->phone_amount ?? 0), 2) }} TMT</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="text-center text-muted py-4">
                                    No transactions found.
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
@endsection
