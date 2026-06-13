@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row">
        {{-- LEFT: LIST --}}
        <div class="col-xxl-8">
            <div class="card">
                <div class="card-header">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-8">
                            <form method="GET" action="{{ route('payments') }}">
                                <div class="search-box">
                                    <input type="text" name="q" class="form-control"
                                        placeholder="{{ __('pages/payments.search_placeholder') }}"
                                        value="{{ $q ?? request('q') }}">
                                    <i class="ri-search-line search-icon"></i>
                                    @if (is_array(request('ids')) && trim((string) request('q', '')) === '')
                                        @foreach (request('ids') as $hid)
                                            @if (is_numeric($hid))
                                                <input type="hidden" name="ids[]" value="{{ $hid }}">
                                            @endif
                                        @endforeach
                                    @endif

                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card-body">

                    {{-- Empty mode --}}
                    @if (($emptyMode ?? false) === true)
                        <div class="text-center text-muted py-5">
                            <div class="mb-2">
                                <i class="ri-search-line fs-1"></i>
                            </div>
                            <div class="fw-semibold">{{ __('pages/payments.empty_title') }}</div>
                            <div class="small mt-1">
                                {!! __('pages/payments.empty_desc', [
                                    'customers_info' => '<span class="fw-semibold">' . __('pages/payments.customers_info') . '</span>',
                                    'payment' => '<span class="fw-semibold">' . __('pages/payments.payment') . '</span>',
                                ]) !!}
                            </div>
                        </div>
                    @else
                        @php
                            $isPaginator = is_object($customers) && method_exists($customers, 'links');
                            $list = $isPaginator ? $customers : collect($customers);
                        @endphp

                        <div class="table-responsive table-card mb-3">
                            <table class="table align-middle table-nowrap mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('pages/payments.th.name') }}</th>
                                        <th>{{ __('pages/payments.th.contract') }}</th>
                                        <th class="text-end">{{ __('pages/payments.th.remaining') }}</th>
                                        <th class="text-end">{{ __('pages/payments.th.paid_local') }}</th>
                                        <th>{{ __('pages/payments.th.last_paid') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($list as $c)
                                        @php
                                            $isActive =
                                                $selected && (string) $selected->source_id === (string) $c->source_id;
                                            $hasLocal = $c->amount_local !== null && $c->paid_local !== null;
                                            $totalLocal = $hasLocal ? (float) $c->amount_local : null;
                                            $paidLocal = $hasLocal ? (float) $c->paid_local : null;
                                            $remain = $hasLocal ? max($totalLocal - $paidLocal, 0) : null;

                                            $query = request()->query();
                                            $query['id'] = (string) $c->source_id;
                                            $rowUrl = route('payments', $query);

                                        @endphp

                                        <tr class="{{ $isActive ? 'table-active' : '' }}" style="cursor:pointer;"
                                            onclick="window.location='{{ $rowUrl }}'">
                                            <td>
                                                <div class="fw-medium">{{ $c->name ?? '-' }}</div>
                                                <div class="text-muted small">
                                                    {{ $c->branch ?? '-' }} | {{ $c->clientref ?? '-' }}
                                                </div>
                                            </td>

                                            <td>{{ $c->contract ?? '-' }}</td>

                                            <td class="text-end">
                                                @if (!$hasLocal)
                                                    <span
                                                        class="badge bg-warning-subtle text-warning">{{ __('pages/payments.local_missing') }}</span>
                                                @else
                                                    <span
                                                        class="{{ $remain <= 0.00001 ? 'text-success' : 'text-danger' }}">
                                                        {{ number_format($remain, 2) }}
                                                    </span>
                                                @endif
                                            </td>

                                            <td class="text-end">
                                                @if (!$hasLocal)
                                                    —
                                                @else
                                                    {{ number_format($paidLocal, 2) }}
                                                @endif
                                            </td>

                                            <td class="text-muted small">
                                                {{ $c->paid_updated_at ? $c->paid_updated_at->format('Y-m-d H:i') : '-' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                {{ __('pages/payments.no_records') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($isPaginator)
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="text-muted small">
                                    {{ __('pages/payments.total') }}: {{ $customers->total() }} |
                                    {{ __('pages/payments.page') }}: {{ $customers->currentPage() }} /
                                    {{ $customers->lastPage() }}
                                </div>
                                <div>
                                    {{ $customers->onEachSide(1)->links('vendor.pagination.custom') }}
                                </div>
                            </div>
                        @endif
                    @endif

                </div>
            </div>
        </div>

        {{-- RIGHT: DETAIL + PAYMENT --}}
        <div class="col-xxl-4">
            <div class="card">
                <div class="card-body">

                    @if (!$selected)
                        <div class="text-center text-muted py-5">
                            {{ __('pages/payments.select_customer') }}
                        </div>
                    @else
                        @php
                            $hasLocal = $selected->amount_local !== null && $selected->paid_local !== null;

                            $totalLocal = $hasLocal ? (float) $selected->amount_local : null;
                            $paidLocal = $hasLocal ? (float) $selected->paid_local : null;

                            $remain = $hasLocal ? max($totalLocal - $paidLocal, 0) : null;

                            $isClosed = $hasLocal ? $paidLocal >= $totalLocal - 0.01 || $remain <= 0.00001 : true;

                            $lastUser = $selected->paidUpdatedByUser
                                ? $selected->paidUpdatedByUser->firstname . ' ' . $selected->paidUpdatedByUser->lastname
                                : '-';

                            $lastAt = $selected->paid_updated_at
                                ? $selected->paid_updated_at->format('Y-m-d H:i:s')
                                : '-';
                            $remainingJs =
                                $hasLocal && $remain !== null ? number_format((float) $remain, 2, '.', '') : '0.00';

                            $oldReceived = old('pay_amount', '');
                            $oldMethod = old('payment_method', 'cash');
                            $oldCash = old('cash_total', '0.00');
                            $oldCard = old('card_total', '0.00');

                        @endphp

                        <div class="text-center mb-3">
                            <img src="{{ URL::asset('build/images/users/user.jpg') }}"
                                class="avatar-lg rounded-circle img-thumbnail" alt="">
                            <h5 class="mt-3 mb-0">{{ $selected->name ?? '-' }}</h5>
                            {{-- <div class="text-muted">{{ $selected->branch ?? '-' }} | {{ $selected->contract ?? '-' }}
                            </div> --}}
                            <div class="text-muted">
                                {{ $selected->branch ?? '-' }} |

                                @if (!empty($selected->contract))
                                    <a href="javascript:void(0);" class="fw-semibold text-primary" data-bs-toggle="modal"
                                        data-bs-target="#monthlyPaymentsModal">
                                        {{ $selected->contract }}
                                    </a>
                                @else
                                    -
                                @endif
                            </div>
                            <div class="small text-muted">ClientRef: {{ $selected->clientref ?? '-' }}</div>
                            <div class="mt-3 d-flex justify-content-center gap-2 flex-wrap">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3"
                                    data-bs-toggle="modal" data-bs-target="#monthlyPaymentsModal">
                                    <i class="ri-file-list-3-line align-bottom me-1"></i>
                                    {{ __('pages/payments.view_monthly_payments') }}
                                </button>
                                {{-- <a href="{{ route('payments.customer.statement', $selected->logicalref) }}" target="_blank"
                                    class="btn btn-sm btn-outline-success rounded-pill px-3">
                                    <i class="ri-printer-line align-bottom me-1"></i>
                                    Töleg dökümi
                                </a> --}}
                                <a href="{{ route('payments.customer.statement', ['credit' => $selected->source_id, 'lang' => app()->getLocale()]) }}"
                                    target="_blank" class="btn btn-sm btn-outline-success rounded-pill px-3">
                                    <i class="ri-printer-line align-bottom me-1"></i>
                                    {{ __('pages/payments.payment_statement') }} </a>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-borderless mb-0">
                                <tbody>
                                    <tr>
                                        <td class="fw-medium">{{ __('pages/payments.detail.total_local') }}</td>
                                        <td class="text-end">{{ $hasLocal ? number_format($totalLocal, 2) : '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-medium">{{ __('pages/payments.detail.paid_local') }}</td>
                                        <td class="text-end">{{ $hasLocal ? number_format($paidLocal, 2) : '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-medium">{{ __('pages/payments.detail.remaining') }}</td>
                                        <td class="text-end">
                                            @if (!$hasLocal)
                                                <span
                                                    class="badge bg-warning-subtle text-warning">{{ __('pages/payments.local_missing') }}</span>
                                            @else
                                                <span class="{{ $remain <= 0.00001 ? 'text-success' : 'text-danger' }}">
                                                    {{ number_format($remain, 2) }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-medium">{{ __('pages/payments.detail.last_paid_by') }}</td>
                                        <td class="text-end">{{ $lastUser }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-medium">{{ __('pages/payments.detail.last_paid_at') }}</td>
                                        <td class="text-end">{{ $lastAt }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        @if (!$hasLocal)
                            <div class="alert alert-warning mt-3 mb-0">
                                {{ __('pages/payments.alert_local_missing') }}
                            </div>
                        @elseif ($isClosed)
                            <div class="alert alert-success mt-3 mb-0">
                                {{ __('pages/payments.alert_closed') }}
                            </div>
                        @endif

                        <hr>

                        {{-- Last Payment Detail (paid_note) --}}
                        @php
                            $details = [];
                            if (!empty($selected->paid_note)) {
                                foreach (explode('|', $selected->paid_note) as $item) {
                                    $item = trim($item);
                                    if ($item === '' || !str_contains($item, '=')) {
                                        continue;
                                    }
                                    [$key, $value] = array_map('trim', explode('=', $item, 2));
                                    if ($key !== '') {
                                        $details[$key] = $value;
                                    }
                                }
                            }
                        @endphp

                        <div class="mb-2 fw-semibold">{{ __('pages/payments.last_payment_detail') }}</div>

                        @php
                            $labelKey = function ($rawKey) {
                                $k = strtolower(trim((string) $rawKey)); // METHOD, method, Method => method
                                $k = preg_replace('/\s+/', '_', $k);
                                return $k;
                            };
                        @endphp

                        <div class="bg-light rounded p-2 small">
                            @forelse($details as $key => $value)
                                @php
                                    $k = $labelKey($key);
                                    $transKey = "pages/payments.detail_keys.$k";
                                    $label = __($transKey);

                                    if ($label === $transKey) {
                                        $label = ucfirst(str_replace('_', ' ', $k));
                                    }
                                    if ($k === 'method') {
                                        $mvKey = 'pages/payments.method_values.' . strtolower(trim((string) $value));
                                        $mv = __($mvKey);

                                        if ($mv !== $mvKey) {
                                            $value = $mv;
                                        }
                                    }
                                @endphp
                                @if (in_array($k, ['corrected', 'backdated'], true))
                                    @php
                                        $vv = strtolower(trim((string) $value));
                                        if (in_array($vv, ['yes', 'no'], true)) {
                                            $value = __('pages/payments.bool.' . $vv);
                                        }
                                    @endphp
                                @endif

                                <div class="d-flex justify-content-between border-bottom py-1">
                                    <span class="text-muted">{{ $label }}</span>
                                    <span class="fw-semibold">{{ $value }}</span>
                                </div>
                            @empty
                                —
                            @endforelse
                        </div>
                        <hr>
                        {{-- Payment History --}}
                        <div class="mb-2 fw-semibold">{{ __('pages/payments.payment_history_last_10') }}
                        </div>
                        @if (($history ?? collect())->isEmpty())
                            <div class="text-muted small">{{ __('pages/payments.no_payment_history') }}</div>
                        @else
                            <div class="list-group">
                                @foreach ($history as $h)
                                    @php
                                        $u = $h->createdByUser
                                            ? $h->createdByUser->firstname . ' ' . $h->createdByUser->lastname
                                            : '-';

                                        $received = (float) $h->pay_amount;
                                        $change = (float) ($h->change_amount ?? 0);
                                        $applied = $received - $change;
                                        if ($applied < 0) {
                                            $applied = 0;
                                        }

                                        $mKey = 'pages/payments.method_values.' . strtolower(trim((string) $h->method));
                                        $mTxt = __($mKey);

                                        $isVoided = !empty($h->voided_at);
                                        // $isAdmin = auth()->user() && auth()->user()->role->value === 'Admin';
                                    @endphp

                                    {{-- @php
                                        $isAdmin =
                                            auth()->check() && auth()->user()->role === \App\Enums\UserRoleEnum::ADMIN;
                                    @endphp --}}
                                    @php
                                        $isAdmin = auth()->check() && auth()->user()->isAdminLike();
                                    @endphp

                                    @if ($isAdmin && empty($h->voided_at))
                                        <button type="button" class="btn btn-sm btn-outline-primary mt-2 js-correct-btn"
                                            data-bs-toggle="modal" data-bs-target="#correctModal"
                                            data-action="{{ route('payments.correct', $h->id) }}"
                                            data-payment-at="{{ $h->created_at ? $h->created_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i') }}"
                                            data-method="{{ $h->method }}"
                                            data-pay-amount="{{ (float) $h->pay_amount }}"
                                            data-cash="{{ (float) $h->cash_amount }}"
                                            data-card="{{ (float) $h->card_amount }}"
                                            data-note="{{ (string) ($h->note ?? '') }}">
                                            {{ __('pages/payments.actions.correct') }} </button>
                                    @endif

                                    {{-- <div class="list-group-item"> --}}

                                    {{-- <div class="list-group-item {{ $isVoided ? 'bg-danger-subtle border border-danger text-danger' : '' }}"> --}}

                                    <div
                                        class="list-group-item {{ $isVoided
                                            ? 'bg-danger-subtle border-danger'
                                            : (!empty($h->corrected_by)
                                                ? 'bg-warning-subtle border-warning'
                                                : '') }}">

                                        {{-- TOP LINE --}}
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <div class="fw-semibold">

                                                    {{ __('pages/payments.received') }}: {{ number_format($received, 2) }}
                                                    <span class="badge bg-primary-subtle text-primary ms-1">
                                                        {{ $mTxt !== $mKey ? $mTxt : strtoupper((string) $h->method) }}
                                                    </span>

                                                    @if ($isVoided)
                                                        <span class="badge bg-danger-subtle text-danger ms-1">
                                                            {{ __('pages/payments.badges.voided') }}
                                                        </span>
                                                    @endif
                                                </div>

                                                @if ($h->voided_at)
                                                    <div class="text-muted small" style="font-weight:400;">
                                                        {{ __('pages/payments.voided_by') }}:
                                                        {{ optional($h->voidedByUser)->full_name ?? '-' }}
                                                        | {{ $h->voided_at->format('Y-m-d H:i') }}
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="text-muted small d-flex align-items-center gap-2">
                                                <span>{{ $h->created_at ? $h->created_at->format('Y-m-d H:i') : '-' }}</span>

                                                @if ($isAdmin && !$isVoided)
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#voidModal{{ $h->id }}">
                                                        {{ __('pages/payments.actions.void') }} </button>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="text-muted small">
                                            {{ __('pages/payments.cash') }}:
                                            {{ number_format((float) $h->cash_amount, 2) }} |
                                            {{ __('pages/payments.card') }}:
                                            {{ number_format((float) $h->card_amount, 2) }}
                                        </div>

                                        <div class="text-muted small">{{ __('pages/payments.by') }}: {{ $u }}
                                        </div>

                                        @if (!empty($h->corrected_by))
                                            @php
                                                $correctedUser = $h->correctedByUser
                                                    ? $h->correctedByUser->firstname .
                                                        ' ' .
                                                        $h->correctedByUser->lastname
                                                    : 'N/A';

                                                $correctedAt = $h->corrected_at
                                                    ? \Carbon\Carbon::parse($h->corrected_at)->format('Y-m-d H:i')
                                                    : null;
                                            @endphp

                                            <div class="text-muted small">
                                                {{ __('pages/payments.corrected_by') }}: {{ $correctedUser }}
                                                @if ($correctedAt)
                                                    | {{ $correctedAt }}
                                                @endif
                                                @if (!empty($h->correct_reason))
                                                    <div class="small" style="white-space: pre-wrap;">
                                                        {{ $h->correct_reason }}</div>
                                                @endif
                                            </div>
                                        @endif

                                        <div class="text-muted small">
                                            {{ __('pages/payments.remaining') }}:
                                            {{ number_format((float) $h->old_amount_local, 2) }}
                                            → {{ number_format((float) $h->new_amount_local, 2) }}
                                        </div>

                                        @if (!empty($h->note))
                                            <div class="small mt-1" style="white-space: pre-wrap;">{{ $h->note }}
                                            </div>
                                        @endif

                                        {{-- VOID MODAL --}}
                                        @if ($isAdmin && !$isVoided)
                                            <div class="modal fade" id="voidModal{{ $h->id }}" tabindex="-1"
                                                aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <form method="POST"
                                                            action="{{ route('payments.void', ['payment' => $h->id] + request()->query()) }}">
                                                            @csrf

                                                            <div class="modal-header">

                                                                <h5 class="modal-title">
                                                                    {{ __('pages/payments.modals.void.title', ['id' => $h->id]) }}
                                                                </h5>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>

                                                            <div class="modal-body">

                                                                <div class="mb-2 small text-muted">
                                                                    {{ __('pages/payments.modals.void.desc') }}
                                                                </div>
                                                                <label
                                                                    class="form-label">{{ __('pages/payments.modals.void.reason_label') }}</label>

                                                                <textarea name="void_reason" class="form-control" rows="3" required></textarea>
                                                            </div>

                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-light"
                                                                    data-bs-dismiss="modal">
                                                                    {{ __('pages/payments.common.cancel') }}
                                                                </button>
                                                                <button type="submit" class="btn btn-danger">
                                                                    {{ __('pages/payments.modals.void.confirm') }}
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <hr>

                        {{-- PAYMENT FORM --}}
                        <form method="POST" action="{{ route('payments.store', request()->query()) }}" id="payForm">
                            @csrf
                            {{-- Payment Date (Backdate allowed) --}}
                            <div class="mb-3">
                                <label class="form-label">{{ __('pages/payments.form.payment_date') }}</label>
                                <input type="datetime-local" name="payment_at" class="form-control"
                                    value="{{ old('payment_at', now()->format('Y-m-d\TH:i')) }}"
                                    {{ $isClosed ? 'disabled' : '' }}>

                                <div class="small text-muted mt-1">
                                    {{ __('pages/payments.form.payment_date_help') }}
                                </div>
                            </div>
                            <input type="hidden" name="customer_id" value="{{ (string) $selected->source_id }}">

                            <div class="mb-3">
                                <label class="form-label">{{ __('pages/payments.form.received_amount') }}</label>
                                <input type="number" min="0" step="0.01" class="form-control text-end"
                                    name="pay_amount" id="payAmount" placeholder="0.00" value="{{ $oldReceived }}"
                                    required {{ $isClosed ? 'disabled' : '' }}>

                                <div class="small text-muted mt-1">
                                    {{ __('pages/payments.form.remaining') }}: <span class="fw-semibold"
                                        id="remainLabel">
                                        {{ $hasLocal ? number_format($remain, 2) : '—' }}
                                    </span>
                                </div>
                            </div>

                            {{-- Live preview (Applied/Change) --}}
                            <div class="border rounded p-2 mb-3 bg-light">
                                <div class="d-flex justify-content-between small py-1">
                                    <span class="text-muted">{{ __('pages/payments.form.applied_to_debt') }}</span>
                                    <span class="fw-semibold" id="appliedPreview">0.00</span>
                                </div>
                                <div class="d-flex justify-content-between small py-1 border-top">
                                    <span class="text-muted">{{ __('pages/payments.form.change_to_customer') }}</span>
                                    <span class="fw-semibold" id="changePreview">0.00</span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('pages/payments.form.payment_method') }}</label>
                                <div class="d-flex gap-2 flex-wrap">
                                    <div class="form-check">
                                        <input class="form-check-input js-method" type="radio" name="payment_method"
                                            id="mCash" value="cash" {{ $oldMethod === 'cash' ? 'checked' : '' }}
                                            {{ $isClosed ? 'disabled' : '' }}>
                                        <label class="form-check-label"
                                            for="mCash">{{ __('pages/payments.form.method_cash') }}</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input js-method" type="radio" name="payment_method"
                                            id="mCard" value="card" {{ $oldMethod === 'card' ? 'checked' : '' }}
                                            {{ $isClosed ? 'disabled' : '' }}>
                                        <label class="form-check-label"
                                            for="mCard">{{ __('pages/payments.form.method_card') }}</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input js-method" type="radio" name="payment_method"
                                            id="mMixed" value="mixed" {{ $oldMethod === 'mixed' ? 'checked' : '' }}
                                            {{ $isClosed ? 'disabled' : '' }}>
                                        <label class="form-check-label"
                                            for="mMixed">{{ __('pages/payments.form.method_mixed') }}</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input js-method" type="radio" name="payment_method"
                                            id="mPhone" value="phone"
                                            {{ old('payment_method', 'cash') === 'phone' ? 'checked' : '' }}
                                            {{ $isClosed ? 'disabled' : '' }}>
                                        <label class="form-check-label"
                                            for="mPhone">{{ __('pages/payments.form.method_phone') }}</label>
                                    </div>
                                </div>
                            </div>

                            {{-- Mixed box --}}
                            <div class="border rounded p-2 mb-3 d-none" id="mixedBox">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label
                                            class="form-label mb-1">{{ __('pages/payments.form.method_cash') }}</label>
                                        <input type="number" min="0" step="0.01"
                                            class="form-control text-end" name="cash_total" id="cashTotal"
                                            value="{{ $oldCash }}" {{ $isClosed ? 'disabled' : '' }}>
                                    </div>
                                    <div class="col-6">
                                        <label
                                            class="form-label mb-1">{{ __('pages/payments.form.method_card') }}</label>
                                        <input type="number" min="0" step="0.01"
                                            class="form-control text-end" name="card_total" id="cardTotal"
                                            value="{{ $oldCard }}" {{ $isClosed ? 'disabled' : '' }}>
                                    </div>
                                </div>

                                <div class="d-flex gap-2 mt-2 flex-wrap">
                                    <button type="button" class="btn btn-sm btn-light" id="btnAllCash"
                                        {{ $isClosed ? 'disabled' : '' }}>
                                        {{ __('pages/payments.form.all_cash') }}
                                    </button>
                                    <button type="button" class="btn btn-sm btn-light" id="btnAllCard"
                                        {{ $isClosed ? 'disabled' : '' }}>
                                        {{ __('pages/payments.form.all_card') }}
                                    </button>
                                    <button type="button" class="btn btn-sm btn-light" id="btnHalf"
                                        {{ $isClosed ? 'disabled' : '' }}>
                                        {{ __('pages/payments.form.half') }}
                                    </button>
                                </div>

                                <div class="small text-muted mt-2">
                                    {{ __('pages/payments.form.mixed_rule') }}
                                </div>
                            </div>

                            <div class="mb-3 d-none" id="phoneNumberBox">
                                <label class="form-label">{{ __('pages/payments.form.receiver_phone_number') }}</label>
                                <input type="text" class="form-control" name="receiver_phone_number"
                                    id="receiverPhoneNumber" value="{{ old('receiver_phone_number') }}"
                                    inputmode="numeric" pattern="[0-9]*"
                                    placeholder="{{ __('pages/payments.form.receiver_phone_number_placeholder') }}"
                                    {{ $isClosed ? 'disabled' : '' }}>
                                <div class="small text-muted mt-1">
                                    {{ __('pages/payments.form.receiver_phone_number_help') }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('pages/payments.form.note') }}</label>
                                <input type="text" class="form-control" name="note"
                                    placeholder="{{ __('pages/payments.form.note_placeholder') }}"
                                    value="{{ old('note') }}" {{ $isClosed ? 'disabled' : '' }}>
                            </div>

                            <button type="submit" class="btn btn-primary w-100" {{ $isClosed ? 'disabled' : '' }}>
                                {{ __('pages/payments.form.save_payment') }}
                            </button>
                        </form>
                    @endif

                </div>
            </div>
        </div>

    </div>

    {{-- monthly payment modal --}}
    @if ($selected)
        <div class="modal fade" id="monthlyPaymentsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">

                    <div class="modal-header bg-light border-bottom">
                        <div>
                            <h5 class="modal-title mb-1">
                                {{ __('pages/monthly_report.th.contract') }}: {{ $selected->contract ?? '-' }}
                            </h5>
                            <div class="text-muted small">
                                {{ $selected->name ?? '-' }} | {{ $selected->branch ?? '-' }} | ClientRef:
                                {{ $selected->clientref ?? '-' }}
                            </div>
                        </div>

                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body bg-light-subtle">

                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <div class="card border-0 shadow-sm mb-0">
                                    <div class="card-body p-3">
                                        <div class="text-muted small">{{ __('pages/monthly_report.th.borrower') }}</div>
                                        <div class="fw-semibold text-truncate">{{ $selected->name ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="card border-0 shadow-sm mb-0">
                                    <div class="card-body p-3">
                                        <div class="text-muted small">{{ __('pages/monthly_report.th.contract') }}</div>
                                        <div class="fw-semibold">{{ $selected->contract ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="card border-0 shadow-sm mb-0">
                                    <div class="card-body p-3">
                                        <div class="text-muted small">{{ __('pages/monthly_report.th.store') }}</div>
                                        <div class="fw-semibold">{{ $selected->branch ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="card border-0 shadow-sm mb-0">
                                    <div class="card-body p-3">
                                        <div class="text-muted small">{{ __('pages/monthly_report.total') }}</div>
                                        <div class="fw-semibold">{{ ($monthlyPayments ?? collect())->count() }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if (($monthlyPayments ?? collect())->isEmpty())
                            <div class="card border-0 shadow-sm mb-0">
                                <div class="card-body text-center text-muted py-5">
                                    <div class="mb-2">
                                        <i class="ri-file-search-line fs-1"></i>
                                    </div>
                                    <div class="fw-semibold">
                                        {{ __('pages/monthly_report.no_records') }}
                                    </div>
                                </div>
                            </div>
                        @else
                            {{-- <div class="card border-0 shadow-sm mb-0">
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-nowrap align-middle mb-0">
                                            <thead class="table-light text-muted">
                                                <tr class="text-uppercase">
                                                    <th>{{ __('pages/monthly_report.th.store') }} /
                                                        {{ __('pages/monthly_report.th.id') }}</th>
                                                    <th>{{ __('pages/monthly_report.th.borrower') }} /
                                                        {{ __('pages/monthly_report.th.passport') }}</th>
                                                    <th>{{ __('pages/monthly_report.th.phone') }} /
                                                        {{ __('pages/monthly_report.th.tiger') }}</th>
                                                    <th>{{ __('pages/monthly_report.th.contract') }}</th>

                                                    <th class="text-end">{{ __('pages/monthly_report.th.kt_expense') }}
                                                    </th>
                                                    <th class="text-end">{{ __('pages/monthly_report.th.dt_income') }}
                                                    </th>

                                                    <th class="text-end">{{ __('pages/monthly_report.th.m1') }}</th>
                                                    <th class="text-end">{{ __('pages/monthly_report.th.m2') }}</th>
                                                    <th class="text-end">{{ __('pages/monthly_report.th.m3') }}</th>
                                                    <th class="text-end">{{ __('pages/monthly_report.th.m4') }}</th>
                                                    <th class="text-end">{{ __('pages/monthly_report.th.m5') }}</th>
                                                    <th class="text-end">{{ __('pages/monthly_report.th.m6') }}</th>

                                                    <th class="text-end">
                                                        {{ __('pages/monthly_report.th.monthly_payment') }}</th>
                                                    <th class="text-end">{{ __('pages/monthly_report.th.balance') }}</th>

                                                    <th>{{ __('pages/monthly_report.th.loan_date') }}</th>
                                                    <th>{{ __('pages/monthly_report.th.end_date') }}</th>

                                                    <th>{{ __('pages/monthly_report.th.category') }}</th>
                                                    <th>{{ __('pages/monthly_report.th.info') }}</th>
                                                    <th>{{ __('pages/monthly_report.th.note') }}</th>

                                                    <th>{{ __('pages/monthly_report.th.will_pay_date') }}</th>
                                                    <th>{{ __('pages/monthly_report.th.status') }}</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                @foreach ($monthlyPayments as $r)
                                                    @php
                                                        $tolerance = 0.15;

                                                        $ktRaw = $r->kt_cykdajy;
                                                        $dtRaw = $r->dt_girdeji;

                                                        $kt =
                                                            $ktRaw === null
                                                                ? null
                                                                : (float) str_replace(
                                                                    [',', ' '],
                                                                    ['.', ''],
                                                                    trim((string) $ktRaw),
                                                                );
                                                        $dt =
                                                            $dtRaw === null
                                                                ? null
                                                                : (float) str_replace(
                                                                    [',', ' '],
                                                                    ['.', ''],
                                                                    trim((string) $dtRaw),
                                                                );

                                                        $rowClass = '';

                                                        if ($kt !== null && $dt !== null) {
                                                            $diff = abs($kt - $dt);

                                                            if ($diff > $tolerance) {
                                                                $rowClass = 'table-danger';
                                                            }
                                                        }
                                                    @endphp

                                                    <tr class="{{ $rowClass }}">
                                                        <td>
                                                            <span class="fw-medium text-primary">
                                                                {{ $r->magazyn ?? '-' }}
                                                            </span>
                                                            <div class="text-muted small">
                                                                #{{ $r->id ?? '-' }}
                                                            </div>
                                                        </td>

                                                        <td>
                                                            <div class="fw-medium">{{ $r->karz_alyjy ?? '-' }}</div>
                                                            <div class="text-muted small">
                                                                {{ $r->pasport_belgisi ?? '-' }}
                                                            </div>
                                                        </td>

                                                        <td>
                                                            <div class="fw-medium">{{ $r->telefon_belgisi ?? '-' }}</div>
                                                            <div class="text-muted small">
                                                                {{ $r->tiger_kody ?? '-' }}
                                                            </div>
                                                        </td>

                                                        <td>
                                                            <div class="fw-medium">{{ $r->sertnama_nomeri ?? '-' }}</div>
                                                        </td>

                                                        <td class="text-end">
                                                            <div class="fw-medium">
                                                                {{ $r->kt_cykdajy === null ? '-' : number_format((float) $r->kt_cykdajy, 2) }}
                                                            </div>
                                                        </td>

                                                        <td class="text-end">
                                                            <div class="fw-medium">
                                                                {{ $r->dt_girdeji === null ? '-' : number_format((float) $r->dt_girdeji, 2) }}
                                                            </div>
                                                        </td>

                                                        <td class="text-end">
                                                            {{ $r->m1 === null ? '-' : number_format((float) $r->m1, 2) }}
                                                        </td>
                                                        <td class="text-end">
                                                            {{ $r->m2 === null ? '-' : number_format((float) $r->m2, 2) }}
                                                        </td>
                                                        <td class="text-end">
                                                            {{ $r->m3 === null ? '-' : number_format((float) $r->m3, 2) }}
                                                        </td>
                                                        <td class="text-end">
                                                            {{ $r->m4 === null ? '-' : number_format((float) $r->m4, 2) }}
                                                        </td>
                                                        <td class="text-end">
                                                            {{ $r->m5 === null ? '-' : number_format((float) $r->m5, 2) }}
                                                        </td>
                                                        <td class="text-end">
                                                            {{ $r->m6 === null ? '-' : number_format((float) $r->m6, 2) }}
                                                        </td>

                                                        <td class="text-end">
                                                            <div class="fw-semibold">
                                                                {{ $r->aylyk_tolegi === null ? '-' : number_format((float) $r->aylyk_tolegi, 2) }}
                                                            </div>
                                                        </td>

                                                        <td class="text-end">
                                                            <div class="fw-semibold">
                                                                {{ $r->galyndy === null ? '-' : number_format((float) $r->galyndy, 2) }}
                                                            </div>
                                                        </td>

                                                        <td>{{ $r->karz_alan_senesi ?? '-' }}</td>
                                                        <td>{{ $r->gutaryan_senesi ?? '-' }}</td>

                                                        <td>{{ $r->kategoriyasy ?? '-' }}</td>
                                                        <td>{{ $r->maglumat ?? '-' }}</td>
                                                        <td>{{ isset($r->bellik) && trim($r->bellik) !== '' ? $r->bellik : '-' }}
                                                        </td>

                                                        <td>
                                                            {{ $r->tolejek_senesi ? \Carbon\Carbon::parse($r->tolejek_senesi)->format('d.m.Y') : '-' }}
                                                        </td>

                                                        <td>
                                                            <span class="badge bg-secondary-subtle text-secondary">
                                                                {{ isset($r->statusy) && trim($r->statusy) !== '' ? $r->statusy : __('pages/monthly_report.status_missing') }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div> --}}
                            <div class="row g-3">
                                @foreach ($monthlyPayments as $r)
                                    @php
                                        $tolerance = 0.15;

                                        $kt = $r->kt_cykdajy === null ? null : (float) $r->kt_cykdajy;
                                        $dt = $r->dt_girdeji === null ? null : (float) $r->dt_girdeji;

                                        $hasDiff = $kt !== null && $dt !== null && abs($kt - $dt) > $tolerance;
                                    @endphp

                                    <div class="col-12">
                                        <div
                                            class="card border-0 shadow-sm mb-0 {{ $hasDiff ? 'border-start border-4 border-danger' : '' }}">
                                            <div class="card-body">

                                                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                                    <div>
                                                        <div class="fw-semibold fs-15">
                                                            {{ $r->karz_alyjy ?? '-' }}
                                                        </div>
                                                        <div class="text-muted small">
                                                            {{ __('pages/monthly_report.th.contract') }}:
                                                            <span
                                                                class="fw-medium">{{ $r->sertnama_nomeri ?? '-' }}</span>
                                                            |
                                                            {{ __('pages/monthly_report.th.store') }}:
                                                            <span class="fw-medium">{{ $r->magazyn ?? '-' }}</span>
                                                            |
                                                            ID:
                                                            <span class="fw-medium">#{{ $r->id ?? '-' }}</span>
                                                        </div>
                                                    </div>

                                                    <div class="text-end">
                                                        <span class="badge bg-secondary-subtle text-secondary">
                                                            {{ isset($r->statusy) && trim($r->statusy) !== '' ? $r->statusy : __('pages/monthly_report.status_missing') }}
                                                        </span>

                                                        @if ($hasDiff)
                                                            <div class="mt-1">
                                                                <span class="badge bg-danger-subtle text-danger">
                                                                    KT / DT tapawut
                                                                </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="row g-3">
                                                    <div class="col-md-3 col-sm-6">
                                                        <div class="text-muted small">
                                                            {{ __('pages/monthly_report.th.phone') }}</div>
                                                        <div class="fw-medium">{{ $r->telefon_belgisi ?? '-' }}</div>
                                                    </div>

                                                    <div class="col-md-3 col-sm-6">
                                                        <div class="text-muted small">
                                                            {{ __('pages/monthly_report.th.passport') }}</div>
                                                        <div class="fw-medium">{{ $r->pasport_belgisi ?? '-' }}</div>
                                                    </div>

                                                    <div class="col-md-3 col-sm-6">
                                                        <div class="text-muted small">
                                                            {{ __('pages/monthly_report.th.tiger') }}</div>
                                                        <div class="fw-medium">{{ $r->tiger_kody ?? '-' }}</div>
                                                    </div>

                                                    <div class="col-md-3 col-sm-6">
                                                        <div class="text-muted small">
                                                            {{ __('pages/monthly_report.th.will_pay_date') }}</div>
                                                        <div class="fw-medium">
                                                            {{ $r->tolejek_senesi ? \Carbon\Carbon::parse($r->tolejek_senesi)->format('d.m.Y') : '-' }}
                                                        </div>
                                                    </div>

                                                    <div class="col-md-3 col-sm-6">
                                                        <div class="text-muted small">
                                                            {{ __('pages/monthly_report.th.kt_expense') }}</div>
                                                        <div class="fw-semibold">
                                                            {{ $r->kt_cykdajy === null ? '-' : number_format((float) $r->kt_cykdajy, 2) }}
                                                        </div>
                                                    </div>

                                                    <div class="col-md-3 col-sm-6">
                                                        <div class="text-muted small">
                                                            {{ __('pages/monthly_report.th.dt_income') }}</div>
                                                        <div class="fw-semibold">
                                                            {{ $r->dt_girdeji === null ? '-' : number_format((float) $r->dt_girdeji, 2) }}
                                                        </div>
                                                    </div>

                                                    <div class="col-md-3 col-sm-6">
                                                        <div class="text-muted small">
                                                            {{ __('pages/monthly_report.th.monthly_payment') }}</div>
                                                        <div class="fw-semibold text-primary">
                                                            {{ $r->aylyk_tolegi === null ? '-' : number_format((float) $r->aylyk_tolegi, 2) }}
                                                        </div>
                                                    </div>

                                                    <div class="col-md-3 col-sm-6">
                                                        <div class="text-muted small">
                                                            {{ __('pages/monthly_report.th.balance') }}</div>
                                                        <div class="fw-semibold">
                                                            {{ $r->galyndy === null ? '-' : number_format((float) $r->galyndy, 2) }}
                                                        </div>
                                                    </div>

                                                    <div class="col-12">
                                                        <div class="border rounded-3 p-2 bg-light">
                                                            <div class="row g-2 text-center">
                                                                <div class="col-md-2 col-4">
                                                                    <div class="text-muted small">M1</div>
                                                                    <div class="fw-medium">
                                                                        {{ $r->m1 === null ? '-' : number_format((float) $r->m1, 2) }}
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-2 col-4">
                                                                    <div class="text-muted small">M2</div>
                                                                    <div class="fw-medium">
                                                                        {{ $r->m2 === null ? '-' : number_format((float) $r->m2, 2) }}
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-2 col-4">
                                                                    <div class="text-muted small">M3</div>
                                                                    <div class="fw-medium">
                                                                        {{ $r->m3 === null ? '-' : number_format((float) $r->m3, 2) }}
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-2 col-4">
                                                                    <div class="text-muted small">M4</div>
                                                                    <div class="fw-medium">
                                                                        {{ $r->m4 === null ? '-' : number_format((float) $r->m4, 2) }}
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-2 col-4">
                                                                    <div class="text-muted small">M5</div>
                                                                    <div class="fw-medium">
                                                                        {{ $r->m5 === null ? '-' : number_format((float) $r->m5, 2) }}
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-2 col-4">
                                                                    <div class="text-muted small">M6</div>
                                                                    <div class="fw-medium">
                                                                        {{ $r->m6 === null ? '-' : number_format((float) $r->m6, 2) }}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-3 col-sm-6">
                                                        <div class="text-muted small">
                                                            {{ __('pages/monthly_report.th.loan_date') }}</div>
                                                        <div class="fw-medium">{{ $r->karz_alan_senesi ?? '-' }}</div>
                                                    </div>

                                                    <div class="col-md-3 col-sm-6">
                                                        <div class="text-muted small">
                                                            {{ __('pages/monthly_report.th.end_date') }}</div>
                                                        <div class="fw-medium">{{ $r->gutaryan_senesi ?? '-' }}</div>
                                                    </div>

                                                    <div class="col-md-3 col-sm-6">
                                                        <div class="text-muted small">
                                                            {{ __('pages/monthly_report.th.category') }}</div>
                                                        <div class="fw-medium">{{ $r->kategoriyasy ?? '-' }}</div>
                                                    </div>

                                                    <div class="col-md-3 col-sm-6">
                                                        <div class="text-muted small">
                                                            {{ __('pages/monthly_report.th.info') }}</div>
                                                        <div class="fw-medium">{{ $r->maglumat ?? '-' }}</div>
                                                    </div>

                                                    <div class="col-12">
                                                        <div class="text-muted small">
                                                            {{ __('pages/monthly_report.th.note') }}</div>
                                                        <div class="fw-medium">
                                                            {{ isset($r->bellik) && trim($r->bellik) !== '' ? $r->bellik : '-' }}
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="modal fade" id="correctModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="#" id="correctForm" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('pages/payments.modals.correct.title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('pages/payments.form.payment_date') }}</label>
                        <input type="datetime-local" name="payment_at" id="c_payment_at" class="form-control" required>
                        <div class="small text-muted mt-1">{{ __('pages/payments.modals.correct.payment_date_help') }}
                        </div>

                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('pages/payments.modals.correct.received_amount') }}</label>

                        <input type="number" min="0" step="0.01" name="pay_amount" id="c_pay_amount"
                            class="form-control text-end" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('pages/payments.modals.correct.payment_method') }}</label>
                        <div class="d-flex gap-2 flex-wrap">
                            <div class="form-check">
                                <input class="form-check-input c-method" type="radio" name="payment_method"
                                    value="cash" id="c_mCash">
                                <label class="form-check-label"
                                    for="c_mCash">{{ __('pages/payments.form.method_cash') }}</label>

                            </div>
                            <div class="form-check">
                                <input class="form-check-input c-method" type="radio" name="payment_method"
                                    value="card" id="c_mCard">
                                <label class="form-check-label"
                                    for="c_mCard">{{ __('pages/payments.form.method_card') }}</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input c-method" type="radio" name="payment_method"
                                    value="mixed" id="c_mMixed">
                                <label class="form-check-label"
                                    for="c_mMixed">{{ __('pages/payments.form.method_mixed') }}</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input c-method" type="radio" name="payment_method"
                                    value="phone" id="c_mPhone">
                                <label class="form-check-label"
                                    for="c_mPhone">{{ __('pages/payments.form.method_phone') }}</label>
                            </div>
                        </div>
                    </div>

                    <div class="border rounded p-2 mb-3 d-none" id="c_mixedBox">
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label mb-1">{{ __('pages/payments.form.method_cash') }}</label>
                                <input type="number" min="0" step="0.01" class="form-control text-end"
                                    name="cash_total" id="c_cash_total" value="0.00">
                            </div>
                            <div class="col-6">
                                <label class="form-label mb-1">{{ __('pages/payments.form.method_card') }}</label>
                                <input type="number" min="0" step="0.01" class="form-control text-end"
                                    name="card_total" id="c_card_total" value="0.00">
                            </div>
                        </div>
                        <div class="small text-muted mt-2">{{ __('pages/payments.form.mixed_rule_pay_amount') }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('pages/payments.modals.correct.note') }}</label>
                        <input type="text" name="note" id="c_note" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('pages/payments.modals.correct.reason_label') }}</label>
                        <input type="text" name="reason" id="c_reason" class="form-control"
                            placeholder="{{ __('pages/payments.modals.correct.reason_placeholder') }}">
                    </div>

                    <div class="alert alert-warning mb-0">
                        {!! __('pages/payments.modals.correct.warning') !!}
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-light" type="button" data-bs-dismiss="modal">
                        {{ __('pages/payments.common.cancel') }}
                    </button>
                    <button class="btn btn-primary" type="submit">
                        {{ __('pages/payments.modals.correct.confirm') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script>
        (function() {
            const payAmount = document.getElementById('payAmount');
            const mixedBox = document.getElementById('mixedBox');
            const cashTotal = document.getElementById('cashTotal');
            const cardTotal = document.getElementById('cardTotal');

            const btnAllCash = document.getElementById('btnAllCash');
            const btnAllCard = document.getElementById('btnAllCard');
            const btnHalf = document.getElementById('btnHalf');

            const appliedPreview = document.getElementById('appliedPreview');
            const changePreview = document.getElementById('changePreview');

            const methods = Array.from(document.querySelectorAll('.js-method'));

            const phoneNumberBox = document.getElementById('phoneNumberBox');
            const receiverPhoneNumber = document.getElementById('receiverPhoneNumber');
            const payForm = document.getElementById('payForm');


            if (!payAmount || methods.length === 0) return;

            if (payAmount.disabled) return;

            // remaining server'dan sabit gelir
            const remainingServer = parseFloat("{{ $remainingJs ?? '0.00' }}") || 0;

            const toNum = (v) => {
                const n = parseFloat((v ?? '').toString().replace(',', '.'));
                return Number.isFinite(n) ? n : 0;
            };

            const getMethod = () => (methods.find(x => x.checked)?.value || 'cash');
            const clamp = (n, min, max) => Math.max(min, Math.min(max, n));

            const refreshPreview = () => {
                const received = toNum(payAmount.value);
                const applied = Math.min(received, remainingServer);
                const change = received - applied;

                if (appliedPreview) appliedPreview.textContent = applied.toFixed(2);
                if (changePreview) changePreview.textContent = (change > 0 ? change : 0).toFixed(2);
            };

            const normalizeMixed = (changed) => {
                if (!cashTotal || !cardTotal) return;

                const total = toNum(payAmount.value);
                let c = toNum(cashTotal.value);
                let k = toNum(cardTotal.value);

                if (total <= 0) {
                    cashTotal.value = '0.00';
                    cardTotal.value = '0.00';
                    return;
                }

                if (changed === 'cash') {
                    c = clamp(c, 0, total);
                    k = total - c;
                } else if (changed === 'card') {
                    k = clamp(k, 0, total);
                    c = total - k;
                } else {
                    if (Math.abs((c + k) - total) > 0.009) {
                        c = clamp(c, 0, total);
                        k = total - c;
                    }
                }

                cashTotal.value = c.toFixed(2);
                cardTotal.value = k.toFixed(2);
            };

            const refresh = () => {
                refreshPreview();

                const m = getMethod();

                if (m === 'mixed') {
                    if (mixedBox) mixedBox.classList.remove('d-none');

                    const total = toNum(payAmount.value);
                    if (cashTotal && cardTotal) {
                        if ((toNum(cashTotal.value) + toNum(cardTotal.value)) === 0 && total > 0) {
                            cashTotal.value = total.toFixed(2);
                            cardTotal.value = '0.00';
                        }
                        normalizeMixed();
                    }
                } else {
                    if (mixedBox) mixedBox.classList.add('d-none');
                }

                if (phoneNumberBox && receiverPhoneNumber) {
                    if (m === 'phone') {
                        phoneNumberBox.classList.remove('d-none');
                        receiverPhoneNumber.required = true;
                    } else {
                        phoneNumberBox.classList.add('d-none');
                        receiverPhoneNumber.required = false;
                        receiverPhoneNumber.value = '';
                    }
                }
            };


            methods.forEach(r => r.addEventListener('change', refresh));
            payAmount.addEventListener('input', refresh);

            if (cashTotal) cashTotal.addEventListener('input', () => normalizeMixed('cash'));
            if (cardTotal) cardTotal.addEventListener('input', () => normalizeMixed('card'));

            if (btnAllCash) btnAllCash.addEventListener('click', () => {
                const total = toNum(payAmount.value);
                if (!cashTotal || !cardTotal) return;
                cashTotal.value = total.toFixed(2);
                cardTotal.value = '0.00';
                normalizeMixed();
            });

            if (btnAllCard) btnAllCard.addEventListener('click', () => {
                const total = toNum(payAmount.value);
                if (!cashTotal || !cardTotal) return;
                cashTotal.value = '0.00';
                cardTotal.value = total.toFixed(2);
                normalizeMixed();
            });

            if (btnHalf) btnHalf.addEventListener('click', () => {
                const total = toNum(payAmount.value);
                if (!cashTotal || !cardTotal) return;
                const half = total / 2;
                cashTotal.value = half.toFixed(2);
                cardTotal.value = (total - half).toFixed(2);
                normalizeMixed();
            });

            if (receiverPhoneNumber) {
                receiverPhoneNumber.addEventListener('input', () => {
                    receiverPhoneNumber.value = receiverPhoneNumber.value.replace(/[^0-9]/g, '');
                });
            }

            if (payForm) {
                payForm.addEventListener('submit', (e) => {
                    const m = getMethod();

                    if (m === 'phone' && receiverPhoneNumber) {
                        receiverPhoneNumber.value = receiverPhoneNumber.value.replace(/[^0-9]/g, '');

                        if (receiverPhoneNumber.value.trim() === '') {
                            e.preventDefault();
                            alert("{{ __('pages/payments.form.receiver_phone_number_required') }}");
                            receiverPhoneNumber.focus();
                        }
                    }
                });
            }

            refresh();
        })();
    </script>

    <script>
        (function() {
            const form = document.getElementById('correctForm');
            const modal = document.getElementById('correctModal');
            if (!form || !modal) return;

            const paymentAt = document.getElementById('c_payment_at');
            const payAmount = document.getElementById('c_pay_amount');
            const note = document.getElementById('c_note');
            const reason = document.getElementById('c_reason');

            const mixedBox = document.getElementById('c_mixedBox');
            const cashTotal = document.getElementById('c_cash_total');
            const cardTotal = document.getElementById('c_card_total');

            const methods = Array.from(document.querySelectorAll('.c-method'));

            const toNum = (v) => {
                const n = parseFloat((v ?? '').toString().replace(',', '.'));
                return Number.isFinite(n) ? n : 0;
            };

            const setMethod = (m) => {
                methods.forEach(r => r.checked = (r.value === m));
                if (m === 'mixed') mixedBox.classList.remove('d-none');
                else mixedBox.classList.add('d-none');
            };

            const normalizeMixed = () => {
                if (mixedBox.classList.contains('d-none')) return;
                const total = toNum(payAmount.value);
                let c = toNum(cashTotal.value);
                let k = toNum(cardTotal.value);

                if (total <= 0) {
                    cashTotal.value = '0.00';
                    cardTotal.value = '0.00';
                    return;
                }
                if (Math.abs((c + k) - total) > 0.009) {
                    c = Math.max(0, Math.min(total, c));
                    k = total - c;
                }
                cashTotal.value = c.toFixed(2);
                cardTotal.value = k.toFixed(2);
            };

            document.querySelectorAll('.js-correct-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    form.action = btn.dataset.action;

                    paymentAt.value = btn.dataset.paymentAt || '';
                    payAmount.value = (toNum(btn.dataset.payAmount)).toFixed(2);

                    const m = (btn.dataset.method || 'cash').toLowerCase();
                    setMethod(m);

                    cashTotal.value = (toNum(btn.dataset.cash)).toFixed(2);
                    cardTotal.value = (toNum(btn.dataset.card)).toFixed(2);

                    note.value = btn.dataset.note || '';
                    reason.value = '';
                    normalizeMixed();
                });
            });

            methods.forEach(r => r.addEventListener('change', () => {
                setMethod(methods.find(x => x.checked)?.value || 'cash');
                normalizeMixed();
            }));

            payAmount.addEventListener('input', normalizeMixed);
            cashTotal.addEventListener('input', normalizeMixed);
            cardTotal.addEventListener('input', normalizeMixed);
        })();
    </script>
@endsection
