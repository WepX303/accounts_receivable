@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row">
        {{-- Paid Today --}}
        <div class="col-xl-3 col-md-6">
            <a class="text-decoration-none"
                href="{{ route('customers', array_merge(request()->except('quick_filter'), ['quick_filter' => 'paid_today'])) }}">
                <div class="card card-animate">
                    <div class="card-body">
                        <p class="text-uppercase fw-medium text-muted mb-0">{{ __('pages/customers_index.card_paid_today') }}
                        </p>
                        <div class="d-flex align-items-end justify-content-between mt-4">
                            <div>
                                <h4 class="fs-22 fw-semibold ff-secondary mb-1">
                                    {{ number_format($stats['paid_today_sum'] ?? 0, 2) }}
                                </h4>
                                <span class="badge bg-warning me-1">{{ $stats['paid_today_cnt'] ?? 0 }}</span>
                                <span class="text-muted">{{ __('pages/customers_index.payments') }}</span>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-light rounded fs-3">
                                    <i data-feather="dollar-sign" class="text-success icon-dual-success"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        {{-- Paid Yesterday --}}
        <div class="col-xl-3 col-md-6">
            <a class="text-decoration-none"
                href="{{ route('customers', array_merge(request()->except('quick_filter'), ['quick_filter' => 'paid_yesterday'])) }}">
                <div class="card card-animate">
                    <div class="card-body">
                        <p class="text-uppercase fw-medium text-muted mb-0">
                            {{ __('pages/customers_index.card_paid_yesterday') }}</p>
                        <div class="d-flex align-items-end justify-content-between mt-4">
                            <div>
                                <h4 class="fs-22 fw-semibold ff-secondary mb-1">
                                    {{ number_format($stats['paid_y_sum'] ?? 0, 2) }}
                                </h4>
                                <span class="badge bg-warning me-1">{{ $stats['paid_y_cnt'] ?? 0 }}</span>
                                <span class="text-muted">{{ __('pages/customers_index.payments') }}</span>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-light rounded fs-3">
                                    <i data-feather="calendar" class="text-success icon-dual-success"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        {{-- Has Debt --}}
        <div class="col-xl-3 col-md-6">
            <a class="text-decoration-none"
                href="{{ route('customers', array_merge(request()->except('quick_filter'), ['quick_filter' => 'has_debt'])) }}">
                <div class="card card-animate">
                    <div class="card-body">
                        <p class="text-uppercase fw-medium text-muted mb-0">{{ __('pages/customers_index.card_has_debt') }}
                        </p>
                        <div class="d-flex align-items-end justify-content-between mt-4">
                            <div>
                                <h4 class="fs-22 fw-semibold ff-secondary mb-1">
                                    {{ (int) ($stats['has_debt_cnt'] ?? 0) }}
                                </h4>
                                <span class="text-muted">{{ __('pages/customers_index.customers') }}</span>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-light rounded fs-3">
                                    <i data-feather="alert-circle" class="text-warning icon-dual-warning"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        {{-- Paid Mismatch --}}
        <div class="col-xl-3 col-md-6">
            <a class="text-decoration-none"
                href="{{ route('customers', array_merge(request()->except('quick_filter'), ['quick_filter' => 'paid_mismatch'])) }}">
                <div class="card card-animate">
                    <div class="card-body">
                        <p class="text-uppercase fw-medium text-muted mb-0">
                            {{ __('pages/customers_index.card_paid_mismatch') }}</p>
                        <div class="d-flex align-items-end justify-content-between mt-4">
                            <div>
                                <h4 class="fs-22 fw-semibold ff-secondary mb-1">
                                    {{ (int) ($stats['paid_mismatch_cnt'] ?? 0) }}
                                </h4>
                                <span
                                    class="text-muted">{{ __('pages/customers_index.rows_local_remote_mismatch') }}</span>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-light rounded fs-3">
                                    <i data-feather="shuffle" class="text-danger icon-dual-danger"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>


    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="orderList">
                <div class="card-body border border-dashed border-end-0 border-start-0">
                    <form method="GET" action="{{ route('customers') }}">
                        <div class="row g-3 align-items-end">

                            {{-- Search --}}
                            <div class="col-xxl-6 col-sm-6">
                                <div class="search-box">
                                    <input type="text" class="form-control" name="q" value="{{ request('q') }}"
                                        placeholder="{{ __('pages/customers_index.search_placeholder') }}">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>

                            {{-- ONE Quick Filters Dropdown --}}
                            <div class="col-xxl-4 col-sm-4">
                                <select name="quick_filter" class="form-select" onchange="this.form.submit()">
                                    <option value="all">
                                        {{ __('pages/customers_index.quick_all') }}
                                    </option>

                                    <optgroup label="{{ __('pages/customers_index.qf_payments') }}">
                                        <option value="paid_today" @selected(request('quick_filter') === 'paid_today')>
                                            {{ __('pages/customers_index.paid_today') }}
                                        </option>
                                        <option value="paid_yesterday" @selected(request('quick_filter') === 'paid_yesterday')>
                                            {{ __('pages/customers_index.paid_yesterday') }}
                                        </option>
                                        <option value="paid_7d" @selected(request('quick_filter') === 'paid_7d')>
                                            {{ __('pages/customers_index.paid_last_7_days') }}
                                        </option>
                                        <option value="paid_14d" @selected(request('quick_filter') === 'paid_14d')>
                                            {{ __('pages/customers_index.paid_last_14_days') }}
                                        </option>
                                        <option value="paid_1m" @selected(request('quick_filter') === 'paid_1m')>
                                            {{ __('pages/customers_index.paid_last_1_month') }}
                                        </option>
                                        <option value="paid_3m" @selected(request('quick_filter') === 'paid_3m')>
                                            {{ __('pages/customers_index.paid_last_3_months') }}
                                        </option>
                                        <option value="paid_6m" @selected(request('quick_filter') === 'paid_6m')>
                                            {{ __('pages/customers_index.paid_last_6_months') }}
                                        </option>
                                        <option value="paid_9m" @selected(request('quick_filter') === 'paid_9m')>
                                            {{ __('pages/customers_index.paid_last_9_months') }}
                                        </option>
                                        <option value="paid_12m" @selected(request('quick_filter') === 'paid_12m')>
                                            {{ __('pages/customers_index.paid_last_12_months') }}
                                        </option>
                                    </optgroup>

                                    <optgroup label="{{ __('pages/customers_index.qf_debt') }}">
                                        <option value="has_debt" @selected(request('quick_filter') === 'has_debt')>
                                            {{ __('pages/customers_index.has_debt') }}
                                        </option>
                                        <option value="no_debt" @selected(request('quick_filter') === 'no_debt')>
                                            {{ __('pages/customers_index.no_debt') }}
                                        </option>
                                        <option value="no_payment" @selected(request('quick_filter') === 'no_payment')>
                                            {{ __('pages/customers_index.no_payment') }}
                                        </option>
                                    </optgroup>

                                    <optgroup label="{{ __('pages/customers_index.qf_status') }}">
                                        <option value="blocked" @selected(request('quick_filter') === 'blocked')>
                                            {{ __('pages/customers_index.blocked') }}
                                        </option>
                                        <option value="active" @selected(request('quick_filter') === 'active')>
                                            {{ __('pages/customers_index.state_active') }}
                                        </option>
                                        <option value="bermejek" @selected(request('quick_filter') === 'bermejek')>
                                            {{ __('pages/customers_index.will_not_pay') }}
                                        </option>

                                    </optgroup>

                                </select>
                            </div>

                            {{-- Buttons --}}
                            <div class="col-xxl-2 col-sm-2 d-flex gap-2">
                                {{-- 
                                <a class="btn btn-outline-secondary w-100" href="{{ route('customers') }}">
                                    {{ __('pages/customers_index.reset') }}
                                </a>
                                <a class="btn btn-success w-100"
                                    href="{{ route('customers.export', array_merge(request()->query(), ['lang' => app()->getLocale()])) }}">
                                    Export
                                </a> --}}
                                <a class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-1"
                                    href="{{ route('customers') }}">
                                    <i class="ri-refresh-line"></i>
                                    {{ __('pages/customers_index.reset') }}
                                </a>

                                <a class="btn btn-success w-100 d-flex align-items-center justify-content-center gap-1"
                                    href="{{ route('customers.export', array_merge(request()->query(), ['lang' => app()->getLocale()])) }}">
                                    <i class="ri-file-excel-2-line"></i>
                                    Export
                                </a>
                            </div>
                        </div>
                    </form>
                </div>


                <div class="card-body pt-4">
                    <div>
                        <div class="table-responsive table-card mb-1">
                            <table class="table table-nowrap align-middle" id="orderTable">
                                <thead class="text-muted table-light">
                                    <tr class="text-uppercase">
                                        <th>{{ __('pages/customers_index.id') }}</th>
                                        <th>{{ __('pages/customers_index.customer') }}</th>
                                        <th>{{ __('pages/customers_index.customer_status') }}</th>
                                        <th>{{ __('pages/customers_index.phone') }}</th>
                                        <th>{{ __('pages/customers_index.branch_contract') }}</th>
                                        <th>{{ __('pages/customers_index.amount') }}</th>
                                        <th>{{ __('pages/customers_index.paid') }}</th>
                                        <th>{{ __('pages/customers_index.today_paid') }}</th>
                                        <th>{{ __('pages/customers_index.local_remaining') }}</th>
                                        <th>{{ __('pages/customers_index.payment_status') }}</th>
                                        <th>{{ __('pages/customers_index.note') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="list form-check-all">
                                    @foreach ($credit_users as $c)
                                        @php
                                            $hasLocalPaid = $c->paid_local !== null;

                                            $localPaid = round((float) ($c->paid_local ?? 0), 2);
                                            $remotePaid = round((float) ($c->paid ?? 0), 2);

                                            // LOCAL ve CENTER paid farklı mı?
                                            $paidDiff = $hasLocalPaid && $localPaid !== $remotePaid;
                                        @endphp

                                        <tr class="{{ $paidDiff ? 'bg-danger-subtle' : '' }}">
                                            {{-- ID --}}
                                            <td class="id">
                                                <span class="fw-medium text-primary">
                                                    #{{ $c->logicalref }}
                                                    <div class="text-muted small">
                                                        {{ $c->date_ ? \Carbon\Carbon::parse($c->date_)->format('Y-m-d') : '-' }}
                                                    </div>
                                                </span>
                                            </td>
                                            {{-- Customer --}}
                                            <td class="customer_name">
                                                <div class="fw-medium">{{ $c->name }}</div>
                                                <div class="text-muted small">
                                                    {{ __('pages/customers_index.passport_prefix') }}
                                                    {{ $c->passport ?? '-' }}
                                                </div>
                                            </td>
                                            {{-- Customer Status --}}
                                            <td class="customer_status">
                                                <div class="fw-medium">
                                                    {{ $c->custstatus ?: __('pages/customers_index.unknown') }} /
                                                    @if ($c->active)
                                                        <span class="badge bg-success-subtle text-success text-uppercase">
                                                            {{ __('pages/customers_index.state_active') }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-danger-subtle text-danger text-uppercase">
                                                            {{ __('pages/customers_index.state_blocked') }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="text-muted small">
                                                    {{ $c->clientref ?? '-' }}
                                                </div>
                                            </td>
                                            {{-- Phone --}}
                                            <td class="phone_number">
                                                {{ $c->phone ?? '-' }}
                                            </td>
                                            {{-- Branch | Contract --}}
                                            <td class="branch_contract">
                                                <div class="fw-medium">{{ $c->branch }} / {{ $c->contract }}</div>
                                            </td>
                                            {{-- Amount --}}
                                            <td>
                                                <div
                                                    class="fw-medium {{ $c->amount_local !== null ? 'text-primary' : '' }}">
                                                    {{ number_format($c->amount_local ?? ($c->amount ?? 0), 2) }}
                                                </div>

                                                @if ($c->amount_local !== null)
                                                    <div class="small text-muted">
                                                        {{ __('pages/customers_index.remote_prefix') }}
                                                        {{ number_format($c->amount ?? 0, 2) }}
                                                    </div>
                                                @endif
                                            </td>
                                            {{-- Paid --}}
                                            <td>
                                                <div
                                                    class="fw-medium {{ $c->paid_local !== null ? 'text-primary' : '' }}">
                                                    {{ number_format($c->paid_local ?? ($c->paid ?? 0), 2) }}
                                                </div>

                                                @if ($c->paid_local !== null)
                                                    <div class="small text-muted">
                                                        {{ __('pages/customers_index.remote_prefix') }}
                                                        {{ number_format($c->paid ?? 0, 2) }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="fw-medium text-success">
                                                    {{ number_format((float) ($c->today_paid_sum ?? 0), 2) }}
                                                </div>
                                            </td>
                                            {{-- Local Remaining + Remote Remaining --}}
                                            <td class="text-end">
                                                @if ($c->local_remaining === null)
                                                    <span class="badge bg-warning-subtle text-warning">
                                                        {{ __('pages/customers_index.local_missing') }}
                                                    </span>

                                                    {{-- LOCAL yoksa yine de remote remaining göster --}}
                                                    <div class="small text-muted mt-1">
                                                        {{ __('pages/customers_index.remote_prefix') }}
                                                        {{ number_format($c->remote_remaining ?? 0, 2) }}
                                                    </div>
                                                @else
                                                    <div class="fw-medium text-primary">
                                                        {{ number_format($c->local_remaining, 2) }}
                                                    </div>

                                                    {{-- LOCAL varsa remote remaining’i altına yaz --}}
                                                    <div class="small text-muted">
                                                        {{ __('pages/customers_index.remote_prefix') }}
                                                        {{ number_format($c->remote_remaining ?? 0, 2) }}
                                                    </div>
                                                @endif
                                            </td>
                                            {{-- Status --}}
                                            <td>
                                                {{-- <div class="badge bg-secondary-subtle text-secondary">
                                                    {{ $c->status ?? __('pages/customers_index.unknown') }}
                                                </div> --}}
                                                <div class="badge bg-secondary-subtle text-secondary">
                                                    {{ isset($c->status) && trim($c->status) !== '' ? $c->status : __('pages/customers_index.status_missing') }}
                                                </div>

                                            </td>

                                            {{-- Note --}}
                                            <td>
                                                {{ $c->note ?? '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{-- Pagination --}}
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="text-muted small">
                                {{ __('pages/customers_index.total') }}: {{ $credit_users->total() }} |
                                {{ __('pages/customers_index.page') }}: {{ $credit_users->currentPage() }} /
                                {{ $credit_users->lastPage() }}
                            </div>
                            <div>
                                {{ $credit_users->onEachSide(1)->links('vendor.pagination.custom') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end col-->
    </div>
    <!--end row-->
@endsection
@section('script')
    <script src="{{ URL::asset('build/libs/list.js/list.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
@endsection
