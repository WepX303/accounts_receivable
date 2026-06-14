@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div>
                        <h4 class="mb-1">Payment Calendar Report</h4>
                        <p class="text-muted mb-0">
                            Daily expected payments, received payments, difference and collection percentage.
                        </p>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('reports.payment-calendar', ['month' => $previousMonth]) }}"
                            class="btn btn-light border">
                            Previous Month
                        </a>

                        <form method="GET" action="{{ route('reports.payment-calendar') }}">
                            <input type="month" name="month" class="form-control"
                                value="{{ $currentMonth->format('Y-m') }}" onchange="this.form.submit()">
                        </form>

                        <a href="{{ route('reports.payment-calendar', ['month' => $nextMonth]) }}"
                            class="btn btn-light border">
                            Next Month
                        </a>

                        <a href="{{ route('reports.payment-calendar.export', ['month' => $currentMonth->format('Y-m')]) }}"
                            class="btn btn-success">
                            <i class="ri-file-excel-2-line"></i>
                            Excel Export
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary --}}
    <div class="row row-cols-xxl-5 row-cols-xl-5 row-cols-lg-3 row-cols-md-2 row-cols-1 g-3">
        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Expected Payments</p>
                    <h4 class="mb-0">{{ number_format($summary['expected_total'], 2) }} TMT</h4>
                    <p class="text-muted mt-3 mb-0">Total expected amount for selected month.</p>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Received Payments</p>
                    <h4 class="mb-0">{{ number_format($summary['received_total'], 2) }} TMT</h4>
                    <p class="text-muted mt-3 mb-0">Total received net payments.</p>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Difference</p>

                    <h4 class="mb-0 {{ $summary['difference_total'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($summary['difference_total'], 2) }} TMT
                    </h4>

                    <p class="text-muted mt-3 mb-0">
                        Received amount minus expected amount.
                    </p>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Change Returned</p>
                    <h4 class="mb-0">{{ number_format($summary['change_total'], 2) }} TMT</h4>
                    <p class="text-muted mt-3 mb-0">Total change returned to customers.</p>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card card-height-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase fs-13 mb-2">Collection Rate</p>
                    <h4 class="mb-0">{{ number_format($summary['percent_total'], 2) }}%</h4>

                    <div class="progress mt-3" style="height: 8px;">
                        <div class="progress-bar" role="progressbar"
                            style="width: {{ min($summary['percent_total'], 100) }}%;"
                            aria-valuenow="{{ $summary['percent_total'] }}" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>

                    <p class="text-muted mt-3 mb-0">
                        Percentage of received payments against expected payments.
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Calendar Table --}}
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex flex-column flex-lg-row justify-content-between gap-2">
                    <div>
                        <h4 class="card-title mb-1">
                            Daily Payment Calendar - {{ $currentMonth->format('F Y') }}
                        </h4>
                        <p class="text-muted mb-0">
                            Each row shows one calendar day with expected, received and missing amount.
                        </p>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">

                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Day</th>

                                    <th class="text-end">
                                        Expected<br>
                                        <small class="text-muted">{{ $currentMonth->format('F Y') }}</small>
                                    </th>
                                    <th class="text-end">
                                        Received<br>
                                        <small class="text-muted">{{ $currentMonth->format('F Y') }}</small>
                                    </th>
                                    <th class="text-end">
                                        Change<br>
                                        <small class="text-muted">{{ $currentMonth->format('F Y') }}</small>
                                    </th>
                                    <th class="text-end">
                                        Difference<br>
                                        <small class="text-muted">{{ $currentMonth->format('F Y') }}</small>
                                    </th>
                                    <th class="text-end">Collection %</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($days as $day)
                                    @php
                                        $statusClass = 'secondary';
                                        $statusText = 'No Expected Payment';

                                        if ($day['expected'] > 0 && $day['percent'] >= 100) {
                                            $statusClass = 'success';
                                            $statusText = 'Completed';
                                        } elseif ($day['expected'] > 0 && $day['received'] > 0) {
                                            $statusClass = 'warning';
                                            $statusText = 'Partially Paid';
                                        } elseif ($day['expected'] > 0 && $day['received'] <= 0 && $day['is_past']) {
                                            $statusClass = 'danger';
                                            $statusText = 'Missing';
                                        } elseif ($day['expected'] > 0 && $day['is_today']) {
                                            $statusClass = 'info';
                                            $statusText = 'Due Today';
                                        } elseif ($day['expected'] > 0 && $day['is_future']) {
                                            $statusClass = 'primary';
                                            $statusText = 'Upcoming';
                                        }
                                    @endphp

                                    <tr class="{{ $day['is_today'] ? 'table-info' : '' }}">
                                        <td style="white-space: nowrap;">
                                            <div class="fw-medium">
                                                {{ $day['date']->format('d.m.Y') }}
                                            </div>

                                            @if ($day['is_today'])
                                                <div class="text-info small">Today</div>
                                            @endif
                                        </td>

                                        <td>{{ $day['day_name'] }}</td>



                                        <td class="text-end">
                                            {{ number_format($day['expected'], 2) }} TMT
                                        </td>

                                        <td class="text-end">
                                            {{ number_format($day['received'], 2) }} TMT
                                        </td>

                                        <td class="text-end">
                                            {{ number_format($day['change'], 2) }} TMT
                                        </td>

                                        <td class="text-end">
                                            <span class="{{ $day['difference'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($day['difference'], 2) }} TMT
                                            </span>
                                        </td>

                                        <td class="text-end" style="min-width: 170px;">
                                            <div class="d-flex align-items-center justify-content-end gap-2">
                                                <div class="progress flex-grow-1" style="height: 7px; max-width: 90px;">
                                                    <div class="progress-bar" role="progressbar"
                                                        style="width: {{ min($day['percent'], 100) }}%;"
                                                        aria-valuenow="{{ $day['percent'] }}" aria-valuemin="0"
                                                        aria-valuemax="100">
                                                    </div>
                                                </div>
                                                <span>{{ number_format($day['percent'], 2) }}%</span>
                                            </div>
                                        </td>

                                        <td>
                                            <span class="badge bg-{{ $statusClass }}-subtle text-{{ $statusClass }}">
                                                {{ $statusText }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>

                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="2">Total</th>

                                    <th class="text-end">
                                        {{ number_format($summary['expected_total'], 2) }} TMT
                                    </th>

                                    <th class="text-end">
                                        {{ number_format($summary['received_total'], 2) }} TMT
                                    </th>

                                    <th class="text-end">
                                        {{ number_format($summary['change_total'], 2) }} TMT
                                    </th>

                                    <th class="text-end">
                                        <span
                                            class="{{ $summary['difference_total'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($summary['difference_total'], 2) }} TMT
                                        </span>
                                    </th>

                                    <th class="text-end">
                                        {{ number_format($summary['percent_total'], 2) }}%
                                    </th>

                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
