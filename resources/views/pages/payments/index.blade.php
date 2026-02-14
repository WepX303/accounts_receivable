@extends('layouts.layouts-horizontal')

@section('content')

    {{-- ✅ Laravel validation errors --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-2">{{ __('pages/payments.validation_fix') }}</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    {{-- ✅ Controller warnings/success --}}
    @if (session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

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


                                    {{-- selected id korunsun --}}
                                    {{-- @if ($selected)
                                        <input type="hidden" name="id" value="{{ (string) $selected->logicalref }}">
                                    @endif --}}


                                    {{-- customers.info’dan ids[] ile gelindiyse koru --}}
                                    {{-- @if (is_array(request('ids')))
                                        @foreach (request('ids') as $hid)
                                            @if (is_numeric($hid))
                                                <input type="hidden" name="ids[]" value="{{ $hid }}">
                                            @endif
                                        @endforeach
                                    @endif --}}

                                    {{-- customers.info’dan ids[] ile gelindiyse koru (AMA sadece q boşken) --}}
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
                                                $selected && (string) $selected->logicalref === (string) $c->logicalref;

                                            $hasLocal = $c->amount_local !== null && $c->paid_local !== null;

                                            $totalLocal = $hasLocal ? (float) $c->amount_local : null;
                                            $paidLocal = $hasLocal ? (float) $c->paid_local : null;

                                            $remain = $hasLocal ? max($totalLocal - $paidLocal, 0) : null;

                                            // Row URL new style: sadece id parametresi
                                            // $rowUrl = route('payments', ['id' => (string) $c->logicalref]);

                                            // ✅ Row URL: mevcut query (ids[], q vs) korunsun, sadece id değişsin
                                            $query = request()->query();
                                            $query['id'] = (string) $c->logicalref;
                                            $rowUrl = route('payments', $query);

                                            // Row URL old style: mevcut query korunsun
                                            // $query = request()->query();
                                            // $query['id'] = (string) $c->logicalref;
                                            // $rowUrl = route('payments', $query);

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

                            // kapanma kuralı: paid_local >= amount_local
                            $isClosed = $hasLocal ? $paidLocal >= $totalLocal - 0.01 || $remain <= 0.00001 : true;

                            $lastUser = $selected->paidUpdatedByUser
                                ? $selected->paidUpdatedByUser->firstname . ' ' . $selected->paidUpdatedByUser->lastname
                                : '-';

                            $lastAt = $selected->paid_updated_at
                                ? $selected->paid_updated_at->format('Y-m-d H:i:s')
                                : '-';
                            $remainingJs =
                                $hasLocal && $remain !== null ? number_format((float) $remain, 2, '.', '') : '0.00';

                            // old inputlar (form korunması)
                            $oldReceived = old('pay_amount', '');
                            $oldMethod = old('payment_method', 'cash');
                            $oldCash = old('cash_total', '0.00');
                            $oldCard = old('card_total', '0.00');

                        @endphp

                        <div class="text-center mb-3">
                            <img src="{{ URL::asset('build/images/users/user.jpg') }}"
                                class="avatar-lg rounded-circle img-thumbnail" alt="">
                            <h5 class="mt-3 mb-0">{{ $selected->name ?? '-' }}</h5>
                            <div class="text-muted">{{ $selected->branch ?? '-' }} | {{ $selected->contract ?? '-' }}
                            </div>
                            <div class="small text-muted">ClientRef: {{ $selected->clientref ?? '-' }}</div>
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

                        {{-- <div class="mb-2 fw-semibold">{{ __('pages/payments.last_payment_detail') }}</div>
                        <div class="bg-light rounded p-2 small">
                            @forelse($details as $key => $value)
                                <div class="d-flex justify-content-between border-bottom py-1">
                                    <span class="text-muted">{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
                                    <span class="fw-semibold">{{ $value }}</span>
                                </div>
                            @empty
                                —
                            @endforelse
                        </div> --}}

                        <div class="mb-2 fw-semibold">{{ __('pages/payments.last_payment_detail') }}</div>

                        @php
                            // paid_note içinden gelen key’leri çeviride aramak için normalize ediyoruz
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

                                    // Eğer çeviri bulunamazsa Laravel aynı key'i döndürür -> fallback
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
                                    @endphp

                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between">
                                            <div class="fw-semibold">
                                                {{ __('pages/payments.received') }}: {{ number_format($received, 2) }}
                                                {{-- <span class="badge bg-primary-subtle text-primary ms-1">
                                                    {{ strtoupper((string) $h->method) }}
                                                </span> --}}
                                                @php
                                                    $mKey =
                                                        'pages/payments.method_values.' .
                                                        strtolower(trim((string) $h->method));
                                                    $mTxt = __($mKey);
                                                @endphp

                                                <span class="badge bg-primary-subtle text-primary ms-1">
                                                    {{ $mTxt !== $mKey ? $mTxt : strtoupper((string) $h->method) }}
                                                </span>
                                                {{-- <span class="badge bg-primary-subtle text-primary ms-1">
                                                    {{ __('pages/payments.method_values.' . strtolower((string) $h->method)) }}
                                                </span> --}}
                                            </div>
                                            <div class="text-muted small">
                                                {{ $h->created_at ? $h->created_at->format('Y-m-d H:i') : '-' }}
                                            </div>
                                        </div>

                                        <div class="text-muted small">
                                            {{ __('pages/payments.applied') }}: {{ number_format($applied, 2) }} |
                                            {{ __('pages/payments.change') }}: {{ number_format($change, 2) }}
                                        </div>

                                        <div class="text-muted small">
                                            {{ __('pages/payments.cash') }}:
                                            {{ number_format((float) $h->cash_amount, 2) }} |
                                            {{ __('pages/payments.card') }}:
                                            {{ number_format((float) $h->card_amount, 2) }}
                                        </div>

                                        <div class="text-muted small">{{ __('pages/payments.by') }}: {{ $u }}
                                        </div>

                                        <div class="text-muted small">
                                            {{ __('pages/payments.remaining') }}:
                                            {{ number_format((float) $h->old_amount_local, 2) }}
                                            → {{ number_format((float) $h->new_amount_local, 2) }}
                                        </div>

                                        @if (!empty($h->note))
                                            <div class="small mt-1" style="white-space: pre-wrap;">{{ $h->note }}
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
                            <input type="hidden" name="customer_id" value="{{ (string) $selected->logicalref }}">

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
                                        <label class="form-label mb-1">{{ __('pages/payments.form.method_cash') }}</label>
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

            refresh();
        })();
    </script>
@endsection
