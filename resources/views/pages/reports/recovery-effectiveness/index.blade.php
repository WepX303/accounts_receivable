@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        <div>
                            <h4 class="mb-1">Recovery Effectiveness Report</h4>
                            <p class="text-muted mb-0">
                                Measures how effectively overdue customer debt was recovered in the selected period.
                            </p>
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <div class="text-muted me-2">
                                Period:
                                <span class="fw-semibold">
                                    {{ $dateFrom->format('d.m.Y') }} - {{ $dateTo->format('d.m.Y') }}
                                </span>
                            </div>

                            <a href="{{ route('reports.recovery-effectiveness.export', request()->query()) }}"
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

    {{-- Filters --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('reports.recovery-effectiveness') }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label">Date From</label>
                                <input type="date" name="date_from" class="form-control"
                                    value="{{ request('date_from', $dateFrom->format('Y-m-d')) }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Date To</label>
                                <input type="date" name="date_to" class="form-control"
                                    value="{{ request('date_to', $dateTo->format('Y-m-d')) }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Branch</label>
                                <select name="branch" class="form-select">
                                    <option value="">All Branches</option>
                                    @foreach ($branches as $b)
                                        <option value="{{ $b }}" {{ $branch === $b ? 'selected' : '' }}>
                                            {{ $b }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Min Recovered</label>
                                <input type="number" min="0" step="0.01" name="min_recovered"
                                    class="form-control" value="{{ $minRecovered }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Sort</label>
                                <select name="sort" class="form-select">
                                    <option value="recovered_desc" {{ $sort === 'recovered_desc' ? 'selected' : '' }}>
                                        Recovered High</option>
                                    <option value="recovered_asc" {{ $sort === 'recovered_asc' ? 'selected' : '' }}>
                                        Recovered Low</option>
                                    <option value="rate_desc" {{ $sort === 'rate_desc' ? 'selected' : '' }}>Rate High
                                    </option>
                                    <option value="rate_asc" {{ $sort === 'rate_asc' ? 'selected' : '' }}>Rate Low</option>
                                    <option value="remaining_desc" {{ $sort === 'remaining_desc' ? 'selected' : '' }}>
                                        Remaining High</option>
                                    <option value="customer_asc" {{ $sort === 'customer_asc' ? 'selected' : '' }}>Customer
                                        A-Z</option>
                                    <option value="branch_asc" {{ $sort === 'branch_asc' ? 'selected' : '' }}>Branch A-Z
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Search</label>
                                <input type="text" name="q" class="form-control"
                                    placeholder="Customer / contract / phone" value="{{ $q }}">
                            </div>

                            <div class="col-md-12">
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn btn-primary" type="submit">
                                        Apply
                                    </button>

                                    <a href="{{ route('reports.recovery-effectiveness') }}" class="btn btn-light border">
                                        Clear
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
                    <p class="text-muted text-uppercase fs-13 mb-2">Overdue Customers</p>
                    <h4 class="mb-0">{{ number_format($summary['overdue_customers']) }}</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100 border-success">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Recovered Customers</p>
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
                    <p class="text-muted text-uppercase fs-13 mb-2">Not Recovered</p>
                    <h4 class="mb-0 text-danger">{{ number_format($summary['not_recovered_customers']) }}</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100 border-primary">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Customer Recovery Rate</p>
                    <h4 class="mb-0 text-primary">{{ number_format($summary['customer_recovery_rate'], 2) }}%</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mt-1">
        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Total Debt</p>
                    <h4 class="mb-0">{{ number_format($summary['total_debt'], 2) }} TMT</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100 border-success">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Net Recovered</p>
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
                    <p class="text-muted text-uppercase fs-13 mb-2">Gross Recovered</p>
                    <h4 class="mb-0">{{ number_format($summary['gross_recovered'], 2) }} TMT</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Change Returned</p>
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
                    <h5 class="mb-0">Branch Recovery Performance</h5>
                </div>

                <div class="card-body">
                    <div class="table-responsive" style="overflow-x:auto;">
                        <table class="table table-hover align-middle mb-0 text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th>Branch</th>
                                    <th class="text-end">Customers</th>
                                    <th class="text-end">Recovered</th>
                                    <th class="text-end">Customer Rate</th>
                                    <th class="text-end">Total Debt</th>
                                    <th class="text-end">Net Recovered</th>
                                    <th class="text-end">Amount Rate</th>
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
                                            No branch recovery data found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            <tfoot class="table-light">
                                <tr>
                                    <th>Total</th>
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
                        <h5 class="mb-0">Customer Recovery Details</h5>
                        <div class="text-muted small mt-1">
                            Customers with remaining debt and their recovery in selected period.
                        </div>
                    </div>

                    <div class="text-muted small">
                        Total: {{ number_format($summary['overdue_customers']) }}
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive" style="overflow-x:auto;">
                        <table class="table table-hover align-middle mb-0 text-nowrap">
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
                                    <th class="text-end">Payments</th>
                                    <th class="text-end">Gross Recovered</th>
                                    <th class="text-end">Change</th>
                                    <th class="text-end">Net Recovered</th>
                                    <th class="text-end">Recovery %</th>
                                    <th>Last Payment</th>
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
                                            No recovery records found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="5">Total</th>
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
