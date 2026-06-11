@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row justify-content-center">
        <div class="col-xxl-10 col-lg-11">

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">{{ __('pages/payments.payment_statement') }}</h5>
                        <div class="text-muted small">
                            {{ $credit->name ?? '-' }} | {{ $credit->branch ?? '-' }} | {{ $credit->contract ?? '-' }}
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="{{ route('payments.customer.statement', ['credit' => $credit->logicalref, 'lang' => 'tk']) }}"
                                class="btn {{ ($lang ?? app()->getLocale()) === 'tk' ? 'btn-primary' : 'btn-outline-primary' }}">
                                TK
                            </a>

                            <a href="{{ route('payments.customer.statement', ['credit' => $credit->logicalref, 'lang' => 'ru']) }}"
                                class="btn {{ ($lang ?? app()->getLocale()) === 'ru' ? 'btn-primary' : 'btn-outline-primary' }}">
                                RU
                            </a>

                            <a href="{{ route('payments.customer.statement', ['credit' => $credit->logicalref, 'lang' => 'en']) }}"
                                class="btn {{ ($lang ?? app()->getLocale()) === 'en' ? 'btn-primary' : 'btn-outline-primary' }}">
                                EN
                            </a>

                            <a href="{{ route('payments.customer.statement', ['credit' => $credit->logicalref, 'lang' => 'tr']) }}"
                                class="btn {{ ($lang ?? app()->getLocale()) === 'tr' ? 'btn-primary' : 'btn-outline-primary' }}">
                                TR
                            </a>
                        </div>

                        <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                            <i class="ri-printer-line align-bottom me-1"></i>
                            {{ __('pages/payments.print') }}
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <h6 class="mb-3">{{ __('pages/payments.statement_customer_info') }}</h6>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="text-muted small">{{ __('pages/customers_index.customer') }}</div>
                            <div class="fw-semibold">{{ $credit->name ?? '-' }}</div>
                        </div>

                        <div class="col-md-4">
                            <div class="text-muted small">{{ __('pages/customers_info.contract') }}</div>
                            <div class="fw-semibold">{{ $credit->contract ?? '-' }}</div>
                        </div>

                        <div class="col-md-4">
                            <div class="text-muted small">{{ __('pages/customers_info.branch') }}</div>
                            <div class="fw-semibold">{{ $credit->branch ?? '-' }}</div>
                        </div>

                        <div class="col-md-4">
                            <div class="text-muted small">{{ __('pages/customers_info.phone') }}</div>
                            <div class="fw-semibold">{{ $credit->phone ?? '-' }}</div>
                        </div>

                        <div class="col-md-4">
                            <div class="text-muted small">{{ __('pages/customers_info.passport') }}</div>
                            <div class="fw-semibold">{{ $credit->passport ?? '-' }}</div>
                        </div>

                        <div class="col-md-4">
                            <div class="text-muted small">{{ __('pages/customers_info.client_ref') }}</div>
                            <div class="fw-semibold">{{ $credit->clientref ?? '-' }}</div>
                        </div>
                    </div>

                    <h6 class="mb-3">{{ __('pages/payments.statement_debt_status') }}</h6>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="border rounded p-3 bg-light">
                                <div class="text-muted small">{{ __('pages/payments.statement_total_debt') }}</div>
                                <div class="fs-5 fw-semibold">
                                    {{ $credit->amount_local !== null ? number_format((float) $credit->amount_local, 2) : '-' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="border rounded p-3 bg-light">
                                <div class="text-muted small">{{ __('pages/customers_index.paid') }}</div>
                                <div class="fs-5 fw-semibold text-success">
                                    {{ $credit->paid_local !== null ? number_format((float) $credit->paid_local, 2) : '-' }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="border rounded p-3 bg-light">
                                <div class="text-muted small">{{ __('pages/payments.remaining') }}</div>
                                <div class="fs-5 fw-semibold text-danger">
                                    {{ $credit->local_remaining !== null ? number_format((float) $credit->local_remaining, 2) : '-' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="mb-3">{{ __('pages/customers_index.payments') }}</h6>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('pages/payments.statement_date') }}</th>
                                    <th>{{ __('pages/payments.detail_keys.method') }}</th>
                                    <th class="text-end">{{ __('pages/payments.received') }}</th>
                                    <th class="text-end">{{ __('pages/payments.change') }}</th>
                                    <th class="text-end">{{ __('pages/payments.applied') }}</th>
                                    <th class="text-end">{{ __('pages/payments.detail_keys.old_remaining') }}</th>
                                    <th class="text-end">{{ __('pages/payments.detail_keys.new_remaining') }}</th>
                                    <th>{{ __('pages/payments.by') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($payments as $p)
                                    @php
                                        $received = (float) $p->pay_amount;
                                        $change = (float) ($p->change_amount ?? 0);
                                        $applied = max($received - $change, 0);

                                        $userName = $p->createdByUser
                                            ? $p->createdByUser->firstname . ' ' . $p->createdByUser->lastname
                                            : ($p->created_by_name ?? '-');

                                        $methodKey = 'pages/payments.method_values.' . strtolower((string) $p->method);
                                        $methodText = __($methodKey);

                                        if ($methodText === $methodKey) {
                                            $methodText = strtoupper((string) $p->method);
                                        }
                                    @endphp

                                    <tr>
                                        <td>{{ $p->id }}</td>
                                        <td>{{ $p->created_at ? $p->created_at->format('Y-m-d H:i') : '-' }}</td>
                                        <td>{{ $methodText }}</td>
                                        <td class="text-end">{{ number_format($received, 2) }}</td>
                                        <td class="text-end">{{ number_format($change, 2) }}</td>
                                        <td class="text-end">{{ number_format($applied, 2) }}</td>
                                        <td class="text-end">
                                            {{ $p->old_amount_local !== null ? number_format((float) $p->old_amount_local, 2) : '-' }}
                                        </td>
                                        <td class="text-end">
                                            {{ $p->new_amount_local !== null ? number_format((float) $p->new_amount_local, 2) : '-' }}
                                        </td>
                                        <td>{{ $userName }}</td>
                                    </tr>

                                    @if ($p->method === 'phone' && !empty($p->receiver_phone_number))
                                        <tr>
                                            <td></td>
                                            <td colspan="8" class="text-muted small">
                                                {{ __('pages/payments.form.receiver_phone_number') }}:
                                                {{ $p->receiver_phone_number }}
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            {{ __('pages/payments.no_payment_history') }}
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

    <style>
        @media print {

            .navbar-header,
            .app-menu,
            .footer,
            .btn,
            .page-title-box {
                display: none !important;
            }

            body {
                background: #fff !important;
            }

            .card {
                border: none !important;
                box-shadow: none !important;
            }

            .content-page,
            .main-content,
            .page-content {
                margin: 0 !important;
                padding: 0 !important;
            }
        }
    </style>
@endsection