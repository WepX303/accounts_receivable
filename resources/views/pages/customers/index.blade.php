@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <!-- card -->
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0">Invoices Sent</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-success fs-14 mb-0">
                                <i class="ri-arrow-right-up-line fs-13 align-middle"></i> +89.24 %
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4">$<span class="counter-value"
                                    data-target="559.25">0</span>k</h4>
                            <span class="badge bg-warning me-1">2,258</span> <span class="text-muted">
                                Invoices sent</span>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-light rounded fs-3">
                                <i data-feather="file-text" class="text-success icon-dual-success"></i>
                            </span>
                        </div>
                    </div>
                </div><!-- end card body -->
            </div><!-- end card -->
        </div><!-- end col -->

        <div class="col-xl-3 col-md-6">
            <!-- card -->
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0">Paid Invoices</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-danger fs-14 mb-0">
                                <i class="ri-arrow-right-down-line fs-13 align-middle"></i> +8.09 %
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4">$<span class="counter-value"
                                    data-target="409.66">0</span>k</h4>
                            <span class="badge bg-warning me-1">1,958</span> <span class="text-muted">
                                Paid by clients</span>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-light rounded fs-3">
                                <i data-feather="check-square" class="text-success icon-dual-success"></i>
                            </span>
                        </div>
                    </div>
                </div><!-- end card body -->
            </div><!-- end card -->
        </div><!-- end col -->
        <div class="col-xl-3 col-md-6">
            <!-- card -->
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0">Unpaid Invoices</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-danger fs-14 mb-0">
                                <i class="ri-arrow-right-down-line fs-13 align-middle"></i> +9.01 %
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4">$<span class="counter-value"
                                    data-target="136.98">0</span>k</h4>
                            <span class="badge bg-warning me-1">338</span> <span class="text-muted">
                                Unpaid by clients</span>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-light rounded fs-3">
                                <i data-feather="clock" class="text-success icon-dual-success"></i>
                            </span>
                        </div>
                    </div>
                </div><!-- end card body -->
            </div><!-- end card -->
        </div><!-- end col -->
        <div class="col-xl-3 col-md-6">
            <!-- card -->
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0">Cancelled Invoices</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-success fs-14 mb-0">
                                <i class="ri-arrow-right-up-line fs-13 align-middle"></i> +7.55 %
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4">$<span class="counter-value"
                                    data-target="84.20">0</span>k</h4>
                            <span class="badge bg-warning me-1">502</span> <span class="text-muted">
                                Cancelled by clients</span>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-light rounded fs-3">
                                <i data-feather="x-octagon" class="text-success icon-dual-success"></i>
                            </span>
                        </div>
                    </div>
                </div><!-- end card body -->
            </div><!-- end card -->
        </div><!-- end col -->
    </div> <!-- end row-->
    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="orderList">
                {{-- <div class="card-body border border-dashed border-end-0 border-start-0">
                    <form method="GET" action="{{ route('customers') }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-xxl-6 col-sm-6">
                                <div class="search-box">
                                    <input type="text" class="form-control" name="q" value="{{ request('q') }}"
                                        placeholder="{{ __('pages/customers_index.search_placeholder') }}">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>

                            <div class="col-xxl-3 col-sm-4">
                                <select name="pay_range" class="form-select" onchange="this.form.submit()">
                                    <option value="all" @selected(request('pay_range', 'all') === 'all')>
                                        {{ __('pages/customers_index.pay_filter_all') }}</option>
                                    <option value="today" @selected(request('pay_range') === 'today')>
                                        {{ __('pages/customers_index.paid_today') }}</option>
                                    <option value="yesterday" @selected(request('pay_range') === 'yesterday')>
                                        {{ __('pages/customers_index.paid_yesterday') }}</option>
                                    <option value="7d" @selected(request('pay_range') === '7d')>
                                        {{ __('pages/customers_index.paid_last_7_days') }}
                                    </option>
                                    <option value="14d" @selected(request('pay_range') === '14d')>
                                        {{ __('pages/customers_index.paid_last_14_days') }}
                                    </option>
                                    <option value="1m" @selected(request('pay_range') === '1m')>
                                        {{ __('pages/customers_index.paid_last_1_month') }}</option>
                                    <option value="3m" @selected(request('pay_range') === '3m')>
                                        {{ __('pages/customers_index.paid_last_3_months') }}</option>
                                    <option value="6m" @selected(request('pay_range') === '6m')>
                                        {{ __('pages/customers_index.paid_last_6_months') }}</option>
                                </select>
                            </div>

                            <div class="col-xxl-3 col-sm-2 d-flex gap-2">
                                <button class="btn btn-primary w-100"
                                    type="submit">{{ __('pages/customers_index.filter') }}</button>
                                <a class="btn btn-outline-secondary w-100"
                                    href="{{ route('customers') }}">{{ __('pages/customers_index.reset') }}</a>
                            </div>
                        </div>
                    </form>
                </div> --}}

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
                                <button class="btn btn-primary w-100" type="submit">
                                    {{ __('pages/customers_index.filter') }}
                                </button>
                                <a class="btn btn-outline-secondary w-100" href="{{ route('customers') }}">
                                    {{ __('pages/customers_index.reset') }}
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
                                        <th>{{ __('pages/customers_index.local_remaining') }}</th>
                                        <th>{{ __('pages/customers_index.payment_status') }}</th>
                                        <th>{{ __('pages/customers_index.note') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="list form-check-all">
                                    @foreach ($credit_users as $c)
                                    
                                        <tr>
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
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
