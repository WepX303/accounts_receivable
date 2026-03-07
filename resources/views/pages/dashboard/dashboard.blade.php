@extends('layouts.layouts-horizontal')

@section('content')
    @php
        $periodKey = $period ?? 'today';

        $periodText = match ($periodKey) {
            'today' => __('admin_dashboard.period.today'),
            'yesterday' => __('admin_dashboard.period.yesterday'),
            'week' => __('admin_dashboard.period.week'),
            'month' => __('admin_dashboard.period.month'),
            'last7' => __('admin_dashboard.period.last7'),
            'last30' => __('admin_dashboard.period.last30'),
            'custom' => __('admin_dashboard.period.custom'),
            default => __('admin_dashboard.period.today'),
        };

        $periodItems = [
            'today' => __('admin_dashboard.period.today'),
            'yesterday' => __('admin_dashboard.period.yesterday'),
            'week' => __('admin_dashboard.period.week'),
            'month' => __('admin_dashboard.period.month'),
            'last7' => __('admin_dashboard.period.last7'),
            'last30' => __('admin_dashboard.period.last30'),
        ];
    @endphp

    <div class="row">
        {{-- 1) Net Tahsilat --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-height-100">
                <div class="card-body">
                    <div class="float-end">
                        <div class="dropdown card-header-dropdown">
                            <a class="text-reset dropdown-btn" href="#" data-bs-toggle="dropdown" aria-haspopup="true"
                                aria-expanded="false">
                                <span class="text-muted fs-18"><i class="mdi mdi-dots-vertical align-middle"></i></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                @foreach ($periodItems as $key => $label)
                                    <a class="dropdown-item"
                                        href="{{ route('dashboard', ['period' => $key]) }}">{{ $label }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-3">
                                <i class="bx bx-dollar-circle text-info"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1 ps-3">
                            <h5 class="text-muted text-uppercase fs-13 mb-0">
                                {{ $periodText }} {{ __('admin_dashboard.net_collection') }}
                            </h5>
                        </div>
                    </div>

                    <div class="mt-4 pt-1">
                        <h4 class="fs-22 fw-semibold ff-secondary mb-0">
                            {{ number_format($kpi['total_net'] ?? 0, 2) }} <small
                                class="text-muted">{{ __('admin_dashboard.currency') }}</small>
                        </h4>
                        <p class="mt-4 mb-0 text-muted">
                            <span class="badge bg-info-subtle text-info mb-0 me-1">
                                <i class="ri-calendar-line align-middle"></i> {{ $periodText }}
                            </span>
                            {{ __('admin_dashboard.net_formula') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2) İşlem Sayısı --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-height-100">
                <div class="card-body">
                    <div class="float-end">
                        <div class="dropdown card-header-dropdown">
                            <a class="text-reset dropdown-btn" href="#" data-bs-toggle="dropdown" aria-haspopup="true"
                                aria-expanded="false">
                                <span class="text-muted fs-18"><i class="mdi mdi-dots-vertical align-middle"></i></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                @foreach ($periodItems as $key => $label)
                                    <a class="dropdown-item"
                                        href="{{ route('dashboard', ['period' => $key]) }}">{{ $label }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-3">
                                <i class="bx bx-wallet text-info"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1 ps-3">
                            <h5 class="text-muted text-uppercase fs-13 mb-0">
                                {{ $periodText }} {{ __('admin_dashboard.tx_count') }}
                            </h5>
                        </div>
                    </div>

                    <div class="mt-4 pt-1">
                        <h4 class="fs-22 fw-semibold ff-secondary mb-0">
                            {{ number_format($kpi['total_count'] ?? 0) }}
                        </h4>
                        <p class="mt-4 mb-0 text-muted">
                            <span class="badge bg-success-subtle text-success mb-0">
                                <i class="ri-bill-line align-middle"></i> {{ __('admin_dashboard.payment_count') }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3) Ortalama Ödeme --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-height-100">
                <div class="card-body">
                    <div class="float-end">
                        <div class="dropdown card-header-dropdown">
                            <a class="text-reset dropdown-btn" href="#" data-bs-toggle="dropdown" aria-haspopup="true"
                                aria-expanded="false">
                                <span class="text-muted fs-18"><i class="mdi mdi-dots-vertical align-middle"></i></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                @foreach ($periodItems as $key => $label)
                                    <a class="dropdown-item"
                                        href="{{ route('dashboard', ['period' => $key]) }}">{{ $label }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-3">
                                <i class="bx bx-bar-chart-alt-2 text-info"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1 ps-3">
                            <h5 class="text-muted text-uppercase fs-13 mb-0">{{ __('admin_dashboard.avg_payment') }}</h5>
                        </div>
                    </div>

                    <div class="mt-4 pt-1">
                        <h4 class="fs-22 fw-semibold ff-secondary mb-0">
                            {{ number_format($kpi['avg_net'] ?? 0, 2) }} <small
                                class="text-muted">{{ __('admin_dashboard.currency') }}</small>
                        </h4>
                        <p class="mt-4 mb-0 text-muted">
                            <span class="badge bg-warning-subtle text-warning mb-0 me-1">
                                <i class="ri-bar-chart-line align-middle"></i> {{ $periodText }}
                            </span>
                            {{ __('admin_dashboard.avg_net_desc') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4) Son Tahsilat --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-height-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-3">
                                <i class="bx bx-time text-info"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1 ps-3">
                            <h5 class="text-muted text-uppercase fs-13 mb-0">{{ __('admin_dashboard.last_collection') }}
                            </h5>
                        </div>
                    </div>

                    <div class="mt-4 pt-1">
                        <h4 class="fs-22 fw-semibold ff-secondary mb-0">
                            {{ !empty($kpi['last_payment_at']) ? \Carbon\Carbon::parse($kpi['last_payment_at'])->format('d.m.Y H:i') : '-' }}
                        </h4>
                        <p class="mt-4 mb-0 text-muted">
                            <span class="badge bg-primary-subtle text-primary mb-0">
                                <i class="ri-time-line align-middle"></i> {{ __('admin_dashboard.date_time') }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 5 CRM widget --}}
    <div class="row">
        <div class="col-xl-12">
            <div class="card crm-widget">
                <div class="card-body p-0">
                    <div class="row row-cols-xxl-5 row-cols-md-3 row-cols-1 g-0">

                        <div class="col">
                            <div class="py-4 px-3">
                                <h5 class="text-muted text-uppercase fs-13">{{ __('admin_dashboard.cash_total') }}</h5>
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="ri-cash-line display-6 text-muted"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h2 class="mb-0">
                                            {{ number_format($kpi['total_cash'] ?? 0, 2) }}
                                            <small class="text-muted">{{ __('admin_dashboard.currency') }}</small>
                                        </h2>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col">
                            <div class="mt-3 mt-md-0 py-4 px-3">
                                <h5 class="text-muted text-uppercase fs-13">{{ __('admin_dashboard.card_total') }}</h5>
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="ri-bank-card-line display-6 text-muted"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h2 class="mb-0">
                                            {{ number_format($kpi['total_card'] ?? 0, 2) }}
                                            <small class="text-muted">{{ __('admin_dashboard.currency') }}</small>
                                        </h2>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- <div class="col">
                            <div class="mt-3 mt-md-0 py-4 px-3">
                                <h5 class="text-muted text-uppercase fs-13">{{ __('admin_dashboard.mixed_net') }}</h5>
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="ri-exchange-dollar-line display-6 text-muted"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h2 class="mb-0">
                                            {{ number_format($kpi['total_mixed'] ?? 0, 2) }}
                                            <small class="text-muted">{{ __('admin_dashboard.currency') }}</small>
                                        </h2>
                                    </div>
                                </div>
                            </div>
                        </div> --}}


                        <div class="col">
                            <div class="mt-3 mt-md-0 py-4 px-3">
                                <h5 class="text-muted text-uppercase fs-13">{{ __('admin_dashboard.phone_total') }}</h5>
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="ri-sim-card-2-line display-6 text-muted"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h2 class="mb-0">
                                            {{ number_format($kpi['total_phone'] ?? 0, 2) }}
                                            <small class="text-muted">{{ __('admin_dashboard.currency') }}</small>
                                        </h2>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col">
                            <div class="mt-3 mt-md-0 py-4 px-3">
                                <h5 class="text-muted text-uppercase fs-13">{{ __('admin_dashboard.all_total') }}</h5>
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="ri-wallet-3-line display-6 text-muted"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h2 class="mb-0">
                                            {{ number_format($kpi['all_total'] ?? 0, 2) }}
                                            <small class="text-muted">{{ __('admin_dashboard.currency') }}</small>
                                        </h2>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- <div class="col">
                            <div class="mt-3 mt-lg-0 py-4 px-3">
                                <h5 class="text-muted text-uppercase fs-13">{{ __('admin_dashboard.avg_payment') }}</h5>
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="ri-line-chart-line display-6 text-muted"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h2 class="mb-0">
                                            {{ number_format($kpi['avg_net'] ?? 0, 2) }}
                                            <small class="text-muted">{{ __('admin_dashboard.currency') }}</small>
                                        </h2>
                                    </div>
                                </div>
                            </div>
                        </div> --}}
                        <div class="col">
                            <div class="mt-3 mt-lg-0 py-4 px-3">
                                <h5 class="text-muted text-uppercase fs-13">{{ __('admin_dashboard.mixed_tx_count') }}
                                </h5>
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="ri-shuffle-line display-6 text-muted"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h2 class="mb-0">
                                            {{ number_format($kpi['mixed_tx_count'] ?? 0) }}
                                        </h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- end row -->
                </div><!-- end card body -->
            </div><!-- end card -->
        </div><!-- end col -->
    </div><!-- end row -->

    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">
                        {{ __('admin_dashboard.cashier_performance') }} ({{ $periodText }})
                    </h4>
                    @if (!empty($topCashier))
                        <span class="badge bg-success-subtle text-success">
                            {{ __('admin_dashboard.top') }}: {{ $topCashier->cashier_name }}
                            ({{ number_format((float) $topCashier->total_net, 2) }} {{ __('admin_dashboard.currency') }})
                        </span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('admin_dashboard.th_cashier') }}</th>
                                    <th class="text-end">{{ __('admin_dashboard.th_tx') }}</th>
                                    <th class="text-end">{{ __('admin_dashboard.th_net_collection') }}</th>
                                    <th class="text-end">{{ __('admin_dashboard.th_cash') }}</th>
                                    <th class="text-end">{{ __('admin_dashboard.th_card') }}</th>
                                    <th class="text-end">{{ __('admin_dashboard.th_phone') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($byCashier as $r)
                                    <tr>
                                        <td>{{ $r->cashier_name }}</td>
                                        <td class="text-end">{{ number_format((int) $r->tx_count) }}</td>
                                        <td class="text-end">{{ number_format((float) $r->total_net, 2) }} <small
                                                class="text-muted">{{ __('admin_dashboard.currency') }}</small></td>
                                        <td class="text-end">{{ number_format((float) $r->total_cash, 2) }}</td>
                                        <td class="text-end">{{ number_format((float) $r->total_card, 2) }}</td>
                                        <td class="text-end">{{ number_format((float) $r->total_phone, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        {{ __('admin_dashboard.no_cashier_tx') }}
                                        <td colspan="6" class="text-center text-muted py-4"></td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">
                        {{ __('admin_dashboard.branch_performance') }} ({{ $periodText }})
                    </h4>
                    @php $topBranch = $byBranch->first(); @endphp
                    @if ($topBranch)
                        <span class="badge bg-success-subtle text-success">
                            {{ __('admin_dashboard.top_branch') }}: {{ $topBranch->branch }}
                            ({{ number_format((float) $topBranch->total_net, 2) }} {{ __('admin_dashboard.currency') }})
                        </span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('admin_dashboard.th_branch') }}</th>
                                    <th class="text-end">{{ __('admin_dashboard.th_tx') }}</th>
                                    <th class="text-end">{{ __('admin_dashboard.th_net_collection') }}</th>
                                    <th class="text-end">{{ __('admin_dashboard.th_cash') }}</th>
                                    <th class="text-end">{{ __('admin_dashboard.th_card') }}</th>
                                    <th class="text-end">{{ __('admin_dashboard.th_phone') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($byBranch as $r)
                                    <tr>
                                        <td>{{ $r->branch }}</td>
                                        <td class="text-end">{{ number_format((int) $r->tx_count) }}</td>
                                        <td class="text-end">{{ number_format((float) $r->total_net, 2) }} <small
                                                class="text-muted">{{ __('admin_dashboard.currency') }}</small></td>
                                        <td class="text-end">{{ number_format((float) $r->total_cash, 2) }}</td>
                                        <td class="text-end">{{ number_format((float) $r->total_card, 2) }}</td>
                                        <td class="text-end">{{ number_format((float) $r->total_phone, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            {{ __('admin_dashboard.no_branch_tx') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="row">
        <div class="col-xxl-12">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">
                        {{ __('admin_dashboard.collection') }} ({{ $periodText }})
                    </h4>

                    <form class="d-flex gap-2 me-3" method="GET" action="{{ route('dashboard') }}">
                        <input type="hidden" name="period" value="custom">

                        <input type="date" name="start" class="form-control"
                            value="{{ request('start') ?? \Carbon\Carbon::parse($start)->format('Y-m-d') }}">

                        <input type="date" name="end" class="form-control"
                            value="{{ request('end') ?? \Carbon\Carbon::parse($end)->format('Y-m-d') }}">

                        <button class="btn btn-primary" type="submit">{{ __('admin_dashboard.apply') }}</button>
                    </form>

                    <div class="flex-shrink-0">
                        <div class="dropdown card-header-dropdown">
                            <a class="text-reset dropdown-btn" href="#" data-bs-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                <span class="fw-semibold text-uppercase fs-12">{{ __('admin_dashboard.period_label') }}:
                                </span>
                                <span class="text-muted">
                                    {{ $periodText }}
                                    <i class="mdi mdi-chevron-down ms-1"></i>
                                </span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                @foreach ($periodItems as $key => $label)
                                    <a class="dropdown-item"
                                        href="{{ route('dashboard', ['period' => $key]) }}">{{ $label }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body pb-0">
                    <div id="sales-forecast-chart" data-colors='["--vz-primary", "--vz-success", "--vz-warning"]'
                        class="apex-charts" dir="ltr"></div>
                </div>
            </div>
        </div>

        <div class="col-xxl-12">
            <div class="card card-height-100">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">
                        {{ __('admin_dashboard.payment_distribution') }} ({{ $periodText }})
                    </h4>
                    <div class="flex-shrink-0">
                        <div class="dropdown card-header-dropdown">
                            <a class="text-reset dropdown-btn" href="#" data-bs-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                <span class="fw-semibold text-uppercase fs-12">{{ __('admin_dashboard.period_label') }}:
                                </span>
                                <span class="text-muted">
                                    {{ $periodText }}
                                    <i class="mdi mdi-chevron-down ms-1"></i>
                                </span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                @foreach ($periodItems as $key => $label)
                                    <a class="dropdown-item {{ ($period ?? 'today') == $key ? 'active' : '' }}"
                                        href="{{ route('dashboard', ['period' => $key]) }}">{{ $label }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- <div class="card-body px-0">
                    <ul class="list-inline main-chart text-center mb-0">
                        <li class="list-inline-item chart-border-left me-0 border-0">
                            <h4>
                                {{ number_format($kpi['total_cash'] ?? 0, 2) }} <small
                                    class="text-muted">{{ __('admin_dashboard.currency') }}</small>
                                <span
                                    class="text-muted d-inline-block fs-13 align-middle ms-2">{{ __('admin_dashboard.cash') }}</span>
                            </h4>
                        </li>
                        <li class="list-inline-item chart-border-left me-0">
                            <h4>
                                {{ number_format($kpi['total_card'] ?? 0, 2) }} <small
                                    class="text-muted">{{ __('admin_dashboard.currency') }}</small>
                                <span
                                    class="text-muted d-inline-block fs-13 align-middle ms-2">{{ __('admin_dashboard.card') }}</span>
                            </h4>
                        </li>
                        <li class="list-inline-item chart-border-left me-0">
                            <h4>
                                {{ number_format($kpi['total_phone'] ?? 0, 2) }} <small
                                    class="text-muted">{{ __('admin_dashboard.currency') }}</small>
                                <span
                                    class="text-muted d-inline-block fs-13 align-middle ms-2">{{ __('admin_dashboard.phone') }}</span>
                            </h4>
                        </li>
                        <li class="list-inline-item chart-border-left me-0">
                            <h4>
                                {{ number_format($kpi['total_mixed'] ?? 0, 2) }} <small
                                    class="text-muted">{{ __('admin_dashboard.currency') }}</small>
                                <span
                                    class="text-muted d-inline-block fs-13 align-middle ms-2">{{ __('admin_dashboard.mixed') }}</span>
                            </h4>
                        </li>
                    </ul>
                    <div id="revenue-expenses-charts" data-colors='["--vz-success","--vz-primary","--vz-warning"]'
                        class="apex-charts" dir="ltr">
                    </div>
                </div> --}}

                <div class="card-body px-0">
                    <ul class="list-inline main-chart text-center mb-0">
                        <li class="list-inline-item chart-border-left me-0 border-0">
                            <h4 class="text-success">
                                {{ number_format($kpi['total_cash'] ?? 0, 2) }}
                                <small class="text-muted">{{ __('admin_dashboard.currency') }}</small>
                                <span class="text-muted d-inline-block fs-13 align-middle ms-2">
                                    {{ __('admin_dashboard.cash') }}
                                </span>
                            </h4>
                        </li>

                        <li class="list-inline-item chart-border-left me-0">
                            <h4 style="color:#0d6efd">
                                {{ number_format($kpi['total_card'] ?? 0, 2) }}
                                <small class="text-muted">{{ __('admin_dashboard.currency') }}</small>
                                <span class="text-muted d-inline-block fs-13 align-middle ms-2">
                                    {{ __('admin_dashboard.card') }}
                                </span>
                            </h4>
                        </li>

                        <li class="list-inline-item chart-border-left me-0">
                            <h4 class="text-warning">
                                {{ number_format($kpi['total_phone'] ?? 0, 2) }}
                                <small class="text-muted">{{ __('admin_dashboard.currency') }}</small>
                                <span class="text-muted d-inline-block fs-13 align-middle ms-2">
                                    {{ __('admin_dashboard.phone') }}
                                </span>
                            </h4>
                        </li>
                    </ul>

                    <div id="revenue-expenses-charts" class="apex-charts" dir="ltr"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">
                        {{ __('admin_dashboard.recent_transactions') }} ({{ $periodText }})
                    </h4>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('admin_dashboard.th_date') }}</th>
                                    <th>{{ __('admin_dashboard.th_branch') }}</th>
                                    <th>{{ __('admin_dashboard.th_cashier') }}</th>
                                    <th>{{ __('admin_dashboard.th_corrected_by') }}</th>
                                    <th>{{ __('admin_dashboard.th_customer') }}</th>
                                    <th>{{ __('admin_dashboard.th_contract') }}</th>
                                    <th>{{ __('admin_dashboard.th_method') }}</th>
                                    <th class="text-end">{{ __('admin_dashboard.th_net') }}</th>
                                    <th class="text-end">{{ __('admin_dashboard.th_cash') }}</th>
                                    <th class="text-end">{{ __('admin_dashboard.th_card') }}</th>
                                    <th class="text-end">{{ __('admin_dashboard.th_phone') }}</th>
                                    <th class="text-end">{{ __('admin_dashboard.th_change') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentAdminPayments as $p)
                                    <tr>
                                        <td>{{ $p->id }}</td>
                                        <td>{{ \Carbon\Carbon::parse($p->created_at)->format('d.m.Y H:i') }}</td>
                                        <td>{{ $p->branch }}</td>
                                        <td>{{ $p->created_by_name ?? 'N/A' }}</td>
                                        <td>
                                            @if ($p->corrected_by)
                                                <div class="fw-medium">
                                                    {{ optional($p->correctedByUser)->full_name ?? 'N/A' }}
                                                </div>
                                                <div class="text-muted small">
                                                    @if ($p->corrected_at)
                                                        {{ \Carbon\Carbon::parse($p->corrected_at)->format('d.m.Y H:i') }}
                                                    @endif
                                                    @if (!empty($p->correct_reason))
                                                        <span class="d-block">{{ $p->correct_reason }}</span>
                                                    @endif
                                                </div>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $p->customer_name }}</td>
                                        <td>{{ $p->customer_contract }}</td>

                                        <td>
                                            @php
                                                $mKey =
                                                    'pages/payments.method_values.' .
                                                    strtolower(trim((string) $p->method));
                                                $mTxt = __($mKey);
                                            @endphp

                                            <span class="badge bg-primary-subtle text-primary ms-1">
                                                {{ $mTxt !== $mKey ? $mTxt : strtoupper((string) $p->method) }}
                                            </span>
                                        </td>

                                        <td class="text-end">{{ number_format((float) $p->net_amount, 2) }}</td>
                                        <td class="text-end">{{ number_format((float) ($p->cash_amount ?? 0), 2) }}</td>
                                        <td class="text-end">{{ number_format((float) ($p->card_amount ?? 0), 2) }}</td>
                                        <td class="text-end">{{ number_format((float) ($p->phone_amount ?? 0), 2) }}</td>
                                        <td class="text-end">{{ number_format((float) ($p->change_amount ?? 0), 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center text-muted py-4">
                                            {{ __('admin_dashboard.no_records') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        window.ADMIN_DASHBOARD = {!! json_encode([
            'daily' => $chartDaily ?? ['labels' => [], 'series' => []],
            // 'payMethods' => [
            //     'cash' => $kpi['total_cash'] ?? 0,
            //     'card' => $kpi['total_card'] ?? 0,
            //     'mixed' => $kpi['total_mixed'] ?? 0,
            //     'phone' => $kpi['total_phone'] ?? 0,
            // ],
            'payMethods' => [
                'cash' => $kpi['total_cash'] ?? 0,
                'card' => $kpi['total_card'] ?? 0,
                'phone' => $kpi['total_phone'] ?? 0,
            ],
        ]) !!};
    </script>

    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/pages/admin-dashboard.init.js') }}"></script>
@endsection
