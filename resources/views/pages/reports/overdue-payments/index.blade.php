@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">

                        <div>
                            <h4 class="mb-1">Overdue Payments Report</h4>

                            <p class="text-muted mb-0">
                                Customers whose expected installment payments are behind schedule.
                            </p>
                        </div>

                        <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2">

                            <div class="text-muted text-sm-end">
                                <div class="small">Report Date</div>
                                <div class="fw-semibold">
                                    {{ $today->format('d.m.Y') }}
                                </div>
                            </div>
                            <a href="{{ route('reports.overdue-payments.export', request()->query()) }}"
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
                    <form method="GET" action="{{ route('reports.overdue-payments') }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-xl-3 col-md-6">
                                <label class="form-label">Search</label>
                                <input type="text" name="q" class="form-control" value="{{ $q }}"
                                    placeholder="Customer, phone, passport, contract...">
                            </div>

                            <div class="col-xl-2 col-md-6">
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

                            <div class="col-xl-2 col-md-6">
                                <label class="form-label">Min Overdue</label>
                                <input type="number" step="0.01" min="0" name="min_overdue" class="form-control"
                                    value="{{ $minOverdue }}">
                            </div>

                            <div class="col-xl-2 col-md-6">
                                <label class="form-label">Min Days</label>
                                <input type="number" min="0" name="min_days" class="form-control"
                                    value="{{ $minDays }}">
                            </div>

                            <div class="col-xl-2 col-md-6">
                                <label class="form-label">Sort</label>
                                <select name="sort" class="form-select">
                                    <option value="overdue_desc" {{ $sort === 'overdue_desc' ? 'selected' : '' }}>Overdue
                                        high to low</option>
                                    <option value="overdue_asc" {{ $sort === 'overdue_asc' ? 'selected' : '' }}>Overdue low
                                        to high</option>
                                    <option value="days_desc" {{ $sort === 'days_desc' ? 'selected' : '' }}>Days high to
                                        low</option>
                                    <option value="days_asc" {{ $sort === 'days_asc' ? 'selected' : '' }}>Days low to high
                                    </option>
                                    <option value="customer_asc" {{ $sort === 'customer_asc' ? 'selected' : '' }}>Customer
                                        A-Z</option>
                                    <option value="branch_asc" {{ $sort === 'branch_asc' ? 'selected' : '' }}>Branch A-Z
                                    </option>
                                </select>
                            </div>

                            <div class="col-xl-1 col-md-6">
                                <button type="submit" class="btn btn-primary w-100">
                                    Filter
                                </button>
                            </div>
                        </div>

                        <div class="mt-3">
                            <a href="{{ route('reports.overdue-payments') }}" class="btn btn-light border btn-sm">
                                Clear Filters
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
                    <p class="text-muted text-uppercase fs-13 mb-2">Customers</p>
                    <h4 class="mb-0">{{ number_format($summary['customers_count']) }}</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Total Debt</p>
                    <h4 class="mb-0">{{ number_format($summary['total_debt'], 2) }} TMT</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Paid</p>
                    <h4 class="mb-0">{{ number_format($summary['total_paid'], 2) }} TMT</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Remaining</p>
                    <h4 class="mb-0">{{ number_format($summary['total_remaining'], 2) }} TMT</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Expected Paid</p>
                    <h4 class="mb-0">{{ number_format($summary['total_expected'], 2) }} TMT</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100 border-danger">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Overdue Amount</p>
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
                        <h5 class="mb-1">Overdue Customer List</h5>
                        <p class="text-muted mb-0">
                            Showing {{ $rows->firstItem() ?? 0 }} - {{ $rows->lastItem() ?? 0 }}
                            of {{ $rows->total() }} records.
                        </p>
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
                                    <th class="text-end">Monthly</th>
                                    <th class="text-end">Due Count</th>
                                    <th class="text-end">Total Debt</th>
                                    <th class="text-end">Paid</th>
                                    <th class="text-end">Expected Paid</th>
                                    <th class="text-end">Overdue</th>
                                    <th class="text-end">Remaining</th>
                                    <th class="text-end">Overdue Days</th>
                                    <th>Credit Date</th>
                                    <th>Last Due Date</th>
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
                                                {{ $row->overdue_days }} days
                                            </span>
                                        </td>

                                        <td>{{ $row->credit_date?->format('d.m.Y') ?? '-' }}</td>
                                        <td>{{ $row->last_due_date?->format('d.m.Y') ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="15" class="text-center text-muted py-4">
                                            No overdue payments found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="7">Total</th>
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
                            Page {{ $rows->currentPage() }} / {{ $rows->lastPage() }}
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
