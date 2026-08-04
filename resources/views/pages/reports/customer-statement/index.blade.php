@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        <div>
                            <h4 class="mb-1">{{ __('pages/reports.customer_statement.title') }}</h4>
                            <p class="text-muted mb-0">
                                {{ __('pages/reports.customer_statement.subtitle') }}
                            </p>
                        </div>

                        @if ($credit)
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <div class="text-muted me-2">
                                    {{ __('pages/reports.common.contract') }}:
                                    <span class="fw-semibold">{{ $credit->contract ?? '-' }}</span>
                                </div>

                                <a href="{{ route('reports.customer-statement.export', [
                                    'contract' => $credit->contract,
                                    'branch' => $credit->branch,
                                ]) }}"
                                    class="btn btn-success">
                                    <i class="ri-file-excel-2-line me-1"></i>
                                    {{ __('pages/reports.common.export_excel') }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Search --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('reports.customer-statement') }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label">{{ __('pages/reports.common.contract') }}</label>
                                <input type="text"
                                       name="contract"
                                       class="form-control"
                                       placeholder="{{ __('pages/reports.customer_statement.contract_placeholder') }}"
                                       value="{{ $contract }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">{{ __('pages/reports.common.phone') }}</label>
                                <input type="text"
                                       name="phone"
                                       class="form-control"
                                       placeholder="{{ __('pages/reports.customer_statement.phone_placeholder') }}"
                                       value="{{ $phone }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">{{ __('pages/reports.common.customer') }}</label>
                                <input type="text"
                                       name="customer"
                                       class="form-control"
                                       placeholder="{{ __('pages/reports.customer_statement.customer_placeholder') }}"
                                       value="{{ $customer }}">
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

                            <div class="col-md-3">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-primary w-100" type="submit">
                                        {{ __('pages/reports.common.search') }}
                                    </button>

                                    <a href="{{ route('reports.customer-statement') }}"
                                       class="btn btn-light border w-100">
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

    @if ($matches->count() > 1)
        <div class="card mb-3">
            <div class="card-header d-flex flex-column flex-lg-row justify-content-between gap-2">
                <div>
                    <h5 class="mb-0">{{ __('pages/reports.customer_statement.select_customer') }}</h5>
                    <div class="text-muted small mt-1">
                        {{ __('pages/reports.customer_statement.multiple_found') }}
                    </div>
                </div>

                <div class="text-muted small">
                    {{ __('pages/reports.customer_statement.found') }}:
                    @if ($matches instanceof \Illuminate\Pagination\LengthAwarePaginator)
                        {{ number_format($matches->total()) }}
                    @else
                        {{ number_format($matches->count()) }}
                    @endif
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive" style="overflow-x:auto;">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('pages/reports.common.credit_id') }}</th>
                                <th>{{ __('pages/reports.common.customer') }}</th>
                                <th>{{ __('pages/reports.common.contract') }}</th>
                                <th>{{ __('pages/reports.common.phone') }}</th>
                                <th>{{ __('pages/reports.common.branch') }}</th>
                                <th>{{ __('pages/reports.common.credit_date') }}</th>
                                <th class="text-end">{{ __('pages/reports.common.total_debt') }}</th>
                                <th class="text-end">{{ __('pages/reports.common.paid') }}</th>
                                <th class="text-end">{{ __('pages/reports.common.remaining') }}</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($matches as $m)
                                @php
                                    $total = (float) ($m->amount_local ?? ($m->amount ?? 0));
                                    $paidAmount = (float) ($m->paid_local ?? ($m->paid ?? 0));
                                    $remainingAmount = max($total - $paidAmount, 0);
                                @endphp

                                <tr>
                                    <td>{{ $m->source_id }}</td>

                                    <td>
                                        <div class="fw-semibold">{{ $m->name ?? '-' }}</div>
                                        <div class="text-muted small">
                                            ClientRef: {{ $m->clientref ?? '-' }}
                                        </div>
                                    </td>

                                    <td>{{ $m->contract ?? '-' }}</td>
                                    <td>{{ $m->phone ?? '-' }}</td>
                                    <td>{{ $m->branch ?? '-' }}</td>

                                    <td>
                                        {{ $m->date_ ? \Carbon\Carbon::parse($m->date_)->format('d.m.Y') : '-' }}
                                    </td>

                                    <td class="text-end">{{ number_format($total, 2) }} TMT</td>
                                    <td class="text-end">{{ number_format($paidAmount, 2) }} TMT</td>
                                    <td class="text-end fw-semibold text-danger">
                                        {{ number_format($remainingAmount, 2) }} TMT
                                    </td>

                                    <td class="text-end">
                                        <a href="{{ route('reports.customer-statement', [
                                            'contract' => $m->contract,
                                            'branch' => $m->branch,
                                        ]) }}"
                                           class="btn btn-sm btn-primary">
                                            {{ __('pages/reports.customer_statement.view_statement') }}
                                            <i class="ri-arrow-right-line ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($matches instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <div class="mt-3">
                        {{ $matches->links('vendor.pagination.custom') }}
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if (! $credit && $matches->count() === 0 && ($contract !== '' || $phone !== '' || $customer !== ''))
        <div class="alert alert-warning">
            {{ __('pages/reports.customer_statement.not_found') }}
        </div>
    @endif

    @if ($credit)
        {{-- Customer info --}}
        <div class="row">
            <div class="col-xl-4">
                <div class="card card-height-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase fs-13 mb-2">{{ __('pages/reports.common.customer') }}</p>
                        <h5 class="mb-2">{{ $credit->name ?? '-' }}</h5>

                        <div class="text-muted small">
                            <div>{{ __('pages/reports.common.credit_id') }}: <span
                                    class="fw-semibold">{{ $credit->source_id }}</span></div>
                            <div>LogicalRef: <span class="fw-semibold">{{ $credit->logicalref ?? '-' }}</span></div>
                            <div>ClientRef: <span class="fw-semibold">{{ $credit->clientref ?? '-' }}</span></div>
                            <div>{{ __('pages/reports.common.passport') }}: <span
                                    class="fw-semibold">{{ $credit->passport ?? '-' }}</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card card-height-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase fs-13 mb-2">
                            {{ __('pages/reports.customer_statement.contract_info') }}</p>
                        <h5 class="mb-2">{{ $credit->contract ?? '-' }}</h5>

                        <div class="text-muted small">
                            <div>{{ __('pages/reports.common.phone') }}: <span
                                    class="fw-semibold">{{ $credit->phone ?? '-' }}</span></div>
                            <div>{{ __('pages/reports.common.branch') }}: <span
                                    class="fw-semibold">{{ $credit->branch ?? '-' }}</span></div>
                            <div>{{ __('pages/reports.common.status') }}: <span
                                    class="fw-semibold">{{ $credit->status ?? '-' }}</span></div>
                            <div>
                                {{ __('pages/reports.common.credit_date') }}:
                                <span class="fw-semibold">
                                    {{ $credit->date_ ? \Carbon\Carbon::parse($credit->date_)->format('d.m.Y') : '-' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card card-height-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase fs-13 mb-2">
                            {{ __('pages/reports.customer_statement.statement_summary') }}</p>

                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ __('pages/reports.common.total_debt') }}</span>
                            <span class="fw-semibold">{{ number_format($summary['total_debt'], 2) }} TMT</span>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ __('pages/reports.common.paid') }}</span>
                            <span class="fw-semibold text-success">{{ number_format($summary['paid'], 2) }} TMT</span>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ __('pages/reports.common.remaining') }}</span>
                            <span class="fw-semibold text-danger">{{ number_format($summary['remaining'], 2) }} TMT</span>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ __('pages/reports.common.payments') }}</span>
                            <span class="fw-semibold">{{ number_format($summary['payment_count']) }}</span>
                        </div>

                        <div class="d-flex justify-content-between">
                            <span>{{ __('pages/reports.customer_statement.voided') }}</span>
                            <span class="fw-semibold text-warning">{{ number_format($summary['voided_count']) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPI cards --}}
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mt-1">
            <div class="col">
                <div class="card card-height-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase fs-13 mb-2">{{ __('pages/reports.common.total_debt') }}</p>
                        <h4 class="mb-0">{{ number_format($summary['total_debt'], 2) }} TMT</h4>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card card-height-100 border-success">
                    <div class="card-body">
                        <p class="text-muted text-uppercase fs-13 mb-2">{{ __('pages/reports.common.paid') }}</p>
                        <h4 class="mb-0 text-success">{{ number_format($summary['paid'], 2) }} TMT</h4>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card card-height-100 border-danger">
                    <div class="card-body">
                        <p class="text-muted text-uppercase fs-13 mb-2">{{ __('pages/reports.common.remaining') }}</p>
                        <h4 class="mb-0 text-danger">{{ number_format($summary['remaining'], 2) }} TMT</h4>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card card-height-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase fs-13 mb-2">{{ __('pages/reports.common.last_payment') }}</p>
                        <h4 class="mb-0">
                            {{ $summary['last_payment_at'] ? \Carbon\Carbon::parse($summary['last_payment_at'])->format('d.m.Y') : '-' }}
                        </h4>
                    </div>
                </div>
            </div>
        </div>

        {{-- Statement --}}
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex flex-column flex-lg-row justify-content-between gap-2">
                        <div>
                            <h5 class="mb-0">{{ __('pages/reports.customer_statement.table_title') }}</h5>
                            <div class="text-muted small mt-1">
                                {{ __('pages/reports.customer_statement.table_hint') }}
                            </div>
                        </div>

                        <div class="text-muted small">
                            {{ __('pages/reports.customer_statement.rows') }}:
                            {{ number_format($statementRows->count()) }}
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive" style="overflow-x:auto;">
                            <table class="table table-hover align-middle mb-0 text-nowrap">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('pages/reports.common.date') }}</th>
                                        <th>{{ __('pages/reports.customer_statement.type') }}</th>
                                        <th>{{ __('pages/reports.customer_statement.description') }}</th>
                                        <th class="text-end">{{ __('pages/reports.customer_statement.debit') }}</th>
                                        <th class="text-end">{{ __('pages/reports.customer_statement.credit') }}</th>
                                        <th class="text-end">{{ __('pages/reports.common.change') }}</th>
                                        <th class="text-end">{{ __('pages/reports.common.net') }}</th>
                                        <th class="text-end">{{ __('pages/reports.customer_statement.balance') }}</th>
                                        <th>{{ __('pages/reports.common.method') }}</th>
                                        <th>{{ __('pages/reports.common.cashier') }}</th>
                                        <th>{{ __('pages/reports.common.note') }}</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse ($statementRows as $row)
                                        @php
                                            $rowClass = $row->is_voided
                                                ? 'table-warning'
                                                : ($row->is_corrected ? 'table-info' : '');
                                        @endphp

                                        <tr class="{{ $rowClass }}">
                                            <td>{{ $row->date ? $row->date->format('d.m.Y H:i') : '-' }}</td>
                                            <td>
                                                @if ($row->is_voided)
                                                    <span class="badge bg-warning-subtle text-warning">{{ $row->type }}</span>
                                                @elseif ($row->is_corrected)
                                                    <span class="badge bg-info-subtle text-info">{{ $row->type }}</span>
                                                @elseif ($row->is_credit_created)
                                                    <span class="badge bg-secondary-subtle text-secondary">{{ $row->type }}</span>
                                                @else
                                                    <span class="badge bg-success-subtle text-success">{{ $row->type }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $row->description }}</td>
                                            <td class="text-end">{{ number_format($row->debit, 2) }} TMT</td>
                                            <td class="text-end text-success">{{ number_format($row->credit, 2) }} TMT</td>
                                            <td class="text-end">{{ number_format($row->change, 2) }} TMT</td>
                                            <td class="text-end fw-semibold">{{ number_format($row->net, 2) }} TMT</td>
                                            <td class="text-end fw-semibold text-danger">
                                                {{ number_format($row->balance, 2) }} TMT
                                            </td>
                                            <td>{{ $row->method }}</td>
                                            <td>{{ $row->cashier }}</td>
                                            <td>{{ $row->note ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="11" class="text-center text-muted py-4">
                                                {{ __('pages/reports.customer_statement.empty') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>

                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="3">{{ __('pages/reports.common.total') }}</th>
                                        <th class="text-end">{{ number_format($summary['total_debt'], 2) }} TMT</th>
                                        <th class="text-end">{{ number_format($summary['paid'], 2) }} TMT</th>
                                        <th></th>
                                        <th></th>
                                        <th class="text-end">{{ number_format($summary['remaining'], 2) }} TMT</th>
                                        <th colspan="3"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                {{ __('pages/reports.customer_statement.prompt') }}
            </div>
        </div>
    @endif
@endsection
