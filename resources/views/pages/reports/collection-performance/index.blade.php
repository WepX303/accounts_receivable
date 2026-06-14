@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        <div>
                            <h4 class="mb-1">Collection Performance Report</h4>
                            <p class="text-muted mb-0">
                                Cashier and branch collection performance based on received payments.
                            </p>
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <div class="text-muted me-2">
                                Period:
                                <span class="fw-semibold">
                                    {{ $dateFrom->format('d.m.Y') }} - {{ $dateTo->format('d.m.Y') }}
                                </span>
                            </div>

                            <a href="{{ route('reports.collection-performance.export', request()->query()) }}"
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
                    <form method="GET" action="{{ route('reports.collection-performance') }}">
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
                                <label class="form-label">Cashier</label>
                                <select name="cashier" class="form-select">
                                    <option value="">All Cashiers</option>
                                    @foreach ($cashiers as $c)
                                        <option value="{{ $c }}" {{ $cashier === $c ? 'selected' : '' }}>
                                            {{ $c }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Method</label>
                                <select name="method" class="form-select">
                                    <option value="all" {{ $method === 'all' ? 'selected' : '' }}>All</option>
                                    <option value="cash" {{ $method === 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="card" {{ $method === 'card' ? 'selected' : '' }}>Card</option>
                                    <option value="phone" {{ $method === 'phone' ? 'selected' : '' }}>Phone</option>
                                </select>
                            </div>


                            <div class="col-md-2">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-primary w-100" type="submit">
                                        Apply
                                    </button>

                                    <a href="{{ route('reports.collection-performance') }}"
                                        class="btn btn-light border w-100">
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

    {{-- Summary --}}
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Transactions</p>
                    <h4 class="mb-0">{{ number_format($summary['tx_count']) }}</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Gross Collection</p>
                    <h4 class="mb-0">{{ number_format($summary['gross_total'], 2) }} TMT</h4>
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

        <div class="col">
            <div class="card card-height-100 border-success">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Net Collection</p>
                    <h4 class="mb-0 text-success">{{ number_format($summary['net_total'], 2) }} TMT</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-5 g-3 mt-1">
        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Cash</p>
                    <h4 class="mb-0">{{ number_format($summary['cash_total'], 2) }} TMT</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Card</p>
                    <h4 class="mb-0">{{ number_format($summary['card_total'], 2) }} TMT</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Phone</p>
                    <h4 class="mb-0">{{ number_format($summary['phone_total'], 2) }} TMT</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Cashiers</p>
                    <h4 class="mb-0">{{ number_format($summary['cashier_count']) }}</h4>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Branches</p>
                    <h4 class="mb-0">{{ number_format($summary['branch_count']) }}</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- Cashier performance --}}
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Cashier Performance</h5>
                </div>

                <div class="card-body">
                    <div class="table-responsive" style="overflow-x:auto;">
                        <table class="table table-hover align-middle mb-0 text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th>Cashier</th>
                                    <th class="text-end">Transactions</th>
                                    <th class="text-end">Gross</th>
                                    <th class="text-end">Change</th>
                                    <th class="text-end">Net</th>
                                    <th class="text-end">Cash</th>
                                    <th class="text-end">Card</th>
                                    <th class="text-end">Phone</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($cashierRows as $row)
                                    <tr>
                                        <td class="fw-semibold">{{ $row->cashier_name }}</td>
                                        <td class="text-end">
                                            <a href="{{ route(
                                                'reports.collection-performance.details',
                                                array_merge(request()->query(), [
                                                    'type' => 'cashier',
                                                    'value' => $row->cashier_name,
                                                    'date_from' => $dateFrom->format('Y-m-d'),
                                                    'date_to' => $dateTo->format('Y-m-d'),
                                                    'method' => $method,
                                                ]),
                                            ) }}"
                                                class="fw-semibold text-primary">
                                                <i class="ri-arrow-right-line ms-1"></i>
                                                {{ number_format((int) $row->tx_count) }}
                                            </a>
                                        </td>
                                        <td class="text-end">{{ number_format((float) $row->gross_total, 2) }} TMT</td>
                                        <td class="text-end">{{ number_format((float) $row->change_total, 2) }} TMT</td>
                                        <td class="text-end fw-semibold text-success">
                                            {{ number_format((float) $row->net_total, 2) }} TMT
                                        </td>
                                        <td class="text-end">{{ number_format((float) $row->cash_total, 2) }} TMT</td>
                                        <td class="text-end">{{ number_format((float) $row->card_total, 2) }} TMT</td>
                                        <td class="text-end">{{ number_format((float) $row->phone_total, 2) }} TMT</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            No cashier data found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            <tfoot class="table-light">
                                <tr>
                                    <th>Total</th>
                                    <th class="text-end">{{ number_format($summary['tx_count']) }}</th>
                                    <th class="text-end">{{ number_format($summary['gross_total'], 2) }} TMT</th>
                                    <th class="text-end">{{ number_format($summary['change_total'], 2) }} TMT</th>
                                    <th class="text-end">{{ number_format($summary['net_total'], 2) }} TMT</th>
                                    <th class="text-end">{{ number_format($summary['cash_total'], 2) }} TMT</th>
                                    <th class="text-end">{{ number_format($summary['card_total'], 2) }} TMT</th>
                                    <th class="text-end">{{ number_format($summary['phone_total'], 2) }} TMT</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- Branch performance --}}
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Branch Performance</h5>
                </div>

                <div class="card-body">
                    <div class="table-responsive" style="overflow-x:auto;">
                        <table class="table table-hover align-middle mb-0 text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th>Branch</th>
                                    <th class="text-end">Transactions</th>
                                    <th class="text-end">Gross</th>
                                    <th class="text-end">Change</th>
                                    <th class="text-end">Net</th>
                                    <th class="text-end">Cash</th>
                                    <th class="text-end">Card</th>
                                    <th class="text-end">Phone</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($branchRows as $row)
                                    <tr>
                                        <td class="fw-semibold">{{ $row->branch_name }}</td>
                                        <td class="text-end">
                                            <a href="{{ route(
                                                'reports.collection-performance.details',
                                                array_merge(request()->query(), [
                                                    'type' => 'branch',
                                                    'value' => $row->branch_name,
                                                    'date_from' => $dateFrom->format('Y-m-d'),
                                                    'date_to' => $dateTo->format('Y-m-d'),
                                                    'method' => $method,
                                                ]),
                                            ) }}"
                                                class="fw-semibold text-primary">
                                                <i class="ri-arrow-right-line ms-1"></i>
                                                {{ number_format((int) $row->tx_count) }}
                                             </a>
                                        </td>
                                        <td class="text-end">{{ number_format((float) $row->gross_total, 2) }} TMT</td>
                                        <td class="text-end">{{ number_format((float) $row->change_total, 2) }} TMT</td>
                                        <td class="text-end fw-semibold text-success">
                                            {{ number_format((float) $row->net_total, 2) }} TMT
                                        </td>
                                        <td class="text-end">{{ number_format((float) $row->cash_total, 2) }} TMT</td>
                                        <td class="text-end">{{ number_format((float) $row->card_total, 2) }} TMT</td>
                                        <td class="text-end">{{ number_format((float) $row->phone_total, 2) }} TMT</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            No branch data found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            <tfoot class="table-light">
                                <tr>
                                    <th>Total</th>
                                    <th class="text-end">{{ number_format($summary['tx_count']) }}</th>
                                    <th class="text-end">{{ number_format($summary['gross_total'], 2) }} TMT</th>
                                    <th class="text-end">{{ number_format($summary['change_total'], 2) }} TMT</th>
                                    <th class="text-end">{{ number_format($summary['net_total'], 2) }} TMT</th>
                                    <th class="text-end">{{ number_format($summary['cash_total'], 2) }} TMT</th>
                                    <th class="text-end">{{ number_format($summary['card_total'], 2) }} TMT</th>
                                    <th class="text-end">{{ number_format($summary['phone_total'], 2) }} TMT</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
