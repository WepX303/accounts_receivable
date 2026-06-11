@extends('layouts.layouts-horizontal')

@section('content')
    @php
        // Controller göndermediyse patlamasın diye default ver
        $stats = $stats ?? [
            'today_total' => 0,
            'today_count' => 0,
            'avg_payment' => 0,
            'last_payment_at' => null,
        ];

        $recentPayments = $recentPayments ?? collect();
    @endphp

    <div class="row mb-3 align-items-center">
        <div class="col-md-8">
            <h4 class="mb-1 fw-semibold">{{ __('menu.dashboard') }}</h4>
            <div class="text-muted">
                {{ __('pages/cashier.welcome_fallback') ?? 'Welcome' }},
                <span class="fw-medium">{{ auth()->user()->full_name ?? auth()->user()->firstname }}</span>
            </div>
        </div>



        <div class="col-md-4 mt-3 mt-md-0">
            <div class="input-group shadow-sm rounded overflow-hidden">
                <span class="input-group-text bg-light border-0">
                    <i class="ri-search-line text-muted"></i>
                </span>
                <input type="text" class="form-control bg-light border-0"
                    placeholder="{{ __('pages/cashier.search_placeholder') }}"
                    onkeydown="if(event.key==='Enter'){ window.location='{{ route('customers.info') }}?q='+encodeURIComponent(this.value) }">
            </div>
        </div>

    </div>

    <!-- Quick Actions -->
    <div class="row g-3">
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <div class="badge bg-primary-subtle text-primary mb-2">
                                <i class="ri-flashlight-line me-1"></i> {{ __('pages/cashier.badge_quick') }}
                            </div>
                            <h5 class="mb-1">{{ __('pages/cashier.quick_pay_title') }}</h5>
                            <p class="text-muted mb-0">{{ __('pages/cashier.quick_pay_desc') }}</p>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title rounded-circle bg-primary-subtle text-primary fs-4">
                                <i class="ri-money-dollar-box-line"></i>
                            </span>
                        </div>
                    </div>

                    <div class="mt-3">
                        <a href="{{ route('payments') }}" class="btn btn-primary w-100">
                            {{ __('pages/cashier.quick_pay_btn') }} <i class="ri-arrow-right-line ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <div class="badge bg-info-subtle text-info mb-2">
                                <i class="ri-user-search-line me-1"></i> {{ __('pages/cashier.badge_customer') }}
                            </div>
                            <h5 class="mb-1">{{ __('pages/cashier.customer_info_title') }}</h5>
                            <p class="text-muted mb-0">{{ __('pages/cashier.customer_info_desc') }}</p>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title rounded-circle bg-info-subtle text-info fs-4">
                                <i class="ri-contacts-line"></i>
                            </span>
                        </div>
                    </div>

                    <div class="mt-3">
                        <a href="{{ route('customers.info') }}" class="btn btn-soft-info w-100">
                            {{ __('pages/cashier.customer_search_btn') }} <i class="ri-arrow-right-line ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-12">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <div class="badge bg-success-subtle text-success mb-2">
                                <i class="ri-bar-chart-box-line me-1"></i> {{ __('pages/cashier.badge_report') }}
                            </div>
                            <h5 class="mb-1">{{ __('pages/cashier.monthly_collection_title') }}</h5>
                            <p class="text-muted mb-0">{{ __('pages/cashier.monthly_collection_desc') }}</p>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title rounded-circle bg-success-subtle text-success fs-4">
                                <i class="ri-folder-chart-line"></i>
                            </span>
                        </div>
                    </div>

                    <div class="mt-3">
                        <a href="{{ route('report') }}" class="btn btn-soft-success w-100">
                            {{ __('pages/cashier.open_report_btn') }} <i class="ri-arrow-right-line ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Today stats -->
    <div class="row g-3 mt-1">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted text-uppercase fs-12">{{ __('pages/cashier.today_collection') }}</div>
                            <div class="fs-22 fw-semibold mt-1">
                                {{ number_format((float) $stats['today_total'], 2, '.', ',') }}
                            </div>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title rounded-circle bg-primary-subtle text-primary fs-4">
                                <i class="ri-hand-coin-line"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mt-2 text-muted fs-12">
                        {{ __('pages/cashier.today_collection_desc') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted text-uppercase fs-12">{{ __('pages/cashier.today_transactions') }}</div>
                            <div class="fs-22 fw-semibold mt-1">
                                {{ (int) $stats['today_count'] }}
                            </div>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title rounded-circle bg-info-subtle text-info fs-4">
                                <i class="ri-receipt-line"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mt-2 text-muted fs-12">
                        {{ __('pages/cashier.today_transactions_desc') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted text-uppercase fs-12">{{ __('pages/cashier.avg_payment') }}</div>
                            <div class="fs-22 fw-semibold mt-1">
                                {{ number_format((float) $stats['avg_payment'], 2, '.', ',') }}
                            </div>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title rounded-circle bg-warning-subtle text-warning fs-4">
                                <i class="ri-line-chart-line"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mt-2 text-muted fs-12">
                        {{ __('pages/cashier.avg_payment_desc') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted text-uppercase fs-12">{{ __('pages/cashier.last_payment') }}</div>
                            <div class="fs-22 fw-semibold mt-1">
                                {{ $stats['last_payment_at'] ? \Carbon\Carbon::parse($stats['last_payment_at'])->format('H:i') : '--:--' }}
                            </div>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title rounded-circle bg-success-subtle text-success fs-4">
                                <i class="ri-time-line"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mt-2 text-muted fs-12">
                        {{ __('pages/cashier.last_payment_desc') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent payments -->
    <div class="row mt-2">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">{{ __('pages/cashier.recent_payments') }}</h5>
                    <a href="{{ route('payments') }}" class="btn btn-sm btn-light">
                        {{ __('pages/cashier.view_all') }} <i class="ri-arrow-right-line ms-1"></i>
                    </a>
                </div>

                <div class="card-body">
                    @if ($recentPayments->isEmpty())
                        <div class="text-center text-muted py-4">
                            <i class="ri-inbox-2-line fs-1 d-block mb-2"></i>
                            {{ __('pages/cashier.no_payment_yet') }}
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('pages/cashier.th_date') }}</th>
                                        <th>{{ __('pages/cashier.th_customer') }}</th>
                                        <th class="text-end">{{ __('pages/cashier.th_amount') }}</th>
                                        <th class="text-end">{{ __('pages/cashier.th_action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentPayments as $p)
                                        <tr>
                                            <td class="text-muted">
                                                {{ \Carbon\Carbon::parse($p->created_at)->format('d.m.Y H:i') }}
                                            </td>
                                            <td>
                                                <div class="fw-medium">{{ $p->customer_name ?? '-' }}</div>
                                                <div class="text-muted fs-12">{{ $p->customer_phone ?? '' }}</div>
                                            </td>
                                            <td class="text-end fw-semibold">
                                                {{ number_format((float) ($p->amount ?? 0), 2, '.', ',') }}
                                            </td>
                                            <td class="text-end">
                                                {{-- İstersen detail sayfan varsa bağlarız --}}
                                                <a href="{{ route('customers.info') }}?q={{ urlencode($p->customer_phone ?? '') }}"
                                                    class="btn btn-sm btn-soft-info">
                                                    {{ __('pages/cashier.customer_btn') }} <i
                                                        class="ri-arrow-right-s-line"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
