@extends('layouts.layouts-horizontal')

@section('content')

    <div class="row">

        {{-- LEFT: TABLE --}}
        <div class="col-xxl-9">
            <div class="card" id="contactList">

                <div class="card-header">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-8">
                            <form method="GET" action="{{ route('customers.info') }}">
                                <div class="search-box">
                                    <input type="text" name="q" class="form-control"
                                        placeholder="{{ __('pages/customers_info.search_placeholder') }}"
                                        value="{{ $q ?? request('q') }}">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </form>
                        </div>

                        <div class="col-md-4 text-md-end">
                            <button type="submit" class="btn btn-primary" form="goPaymentForm" id="goPaymentBtn" disabled>
                                {{ __('pages/customers_info.payment') }} (<span id="selectedCount">0</span>)
                            </button>

                        </div>
                    </div>
                </div>

                <div class="card-body">

                    <form method="GET" action="{{ route('payments') }}" id="goPaymentForm">
                        <div class="table-responsive table-card mb-3">
                            <table class="table align-middle table-nowrap mb-0" id="customerTable">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="width: 50px;"></th>
                                        {{-- <th scope="col">Name</th>
                                        <th scope="col">Phone</th>
                                        <th scope="col">Branch</th>
                                        <th scope="col">Contract</th>
                                        <th scope="col" class="text-end">Amount</th>
                                        <th scope="col" class="text-end">Paid</th>
                                        <th scope="col" class="text-end">Local Remaining</th>
                                        <th scope="col">Active</th> --}}

                                        <th scope="col">{{ __('pages/customers_info.name') }}</th>
                                        <th scope="col">{{ __('pages/customers_info.phone') }}</th>
                                        <th scope="col">{{ __('pages/customers_info.branch') }}</th>
                                        <th scope="col">{{ __('pages/customers_info.contract') }}</th>
                                        <th scope="col" class="text-end">{{ __('pages/customers_info.amount') }}</th>
                                        <th scope="col" class="text-end">{{ __('pages/customers_info.paid') }}</th>
                                        <th scope="col" class="text-end">
                                            {{ __('pages/customers_info.local_remaining') }}</th>
                                        <th scope="col">{{ __('pages/customers_info.active') }}</th>

                                    </tr>
                                </thead>

                                <tbody class="list form-check-all">
                                    @forelse($credit_users_info as $credit)
                                        @php
                                            // Selected row highlight (logicalref model primary key)
                                            $isActive =
                                                isset($selected) &&
                                                $selected &&
                                                (int) $selected->logicalref === (int) $credit->logicalref;

                                            // Center values (DB)
                                            $amountCenter = $credit->amount; // float/int/string olabilir
                                            $paidCenter = $credit->paid;

                                            // Local values (casts: decimal:2)
                                            $amountLocal = $credit->amount_local; // decimal:2 => string/Decimal benzeri dönebilir
                                            $paidLocal = $credit->paid_local;

                                            // Dataset values (string olarak formatlı bas)
                                            $amountLocalText =
                                                $amountLocal !== null ? number_format((float) $amountLocal, 2) : '';
                                            $amountCenterText =
                                                $amountCenter !== null ? number_format((float) $amountCenter, 2) : '';

                                            $paidLocalText =
                                                $paidLocal !== null ? number_format((float) $paidLocal, 2) : '';
                                            $paidCenterText =
                                                $paidCenter !== null ? number_format((float) $paidCenter, 2) : '';

                                            $willPayText = optional($credit->willpaiddate)->format('Y-m-d') ?? '';
                                            $lastNoteText =
                                                optional($credit->lastnoteddate)->format('Y-m-d H:i:s') ?? '';
                                        @endphp

                                        <tr class="js-credit-row {{ $isActive ? 'table-active' : '' }}"
                                            style="cursor:pointer;" data-id="{{ $credit->logicalref }}"
                                            data-name="{{ e($credit->name ?? '') }}"
                                            data-branch="{{ e($credit->branch ?? '') }}"
                                            data-passport="{{ e($credit->passport ?? '') }}"
                                            data-phone="{{ e($credit->phone ?? '') }}"
                                            data-contract="{{ e($credit->contract ?? '') }}"
                                            data-clientref="{{ e($credit->clientref ?? '') }}"
                                            data-status="{{ e($credit->status ?? 'EMPTY') }}"
                                            data-active="{{ !empty($credit->active) ? __('pages/customers_info.state_active') : __('pages/customers_info.state_blocked') }}"
                                            data-note="{{ e($credit->note ?? '') }}"
                                            data-willpaiddate="{{ e($willPayText) }}"
                                            data-lastnoteddate="{{ e($lastNoteText) }}"
                                            data-amount-local="{{ e($amountLocalText) }}"
                                            data-amount-center="{{ e($amountCenterText) }}"
                                            data-paid-local="{{ e($paidLocalText) }}"
                                            data-paid-center="{{ e($paidCenterText) }}">
                                            <th scope="row">
                                                <div class="form-check">
                                                    <input class="form-check-input js-row-check" type="checkbox"
                                                        name="ids[]" value="{{ $credit->logicalref }}">
                                                </div>
                                            </th>

                                            <td class="customer_name">
                                                <div class="fw-medium">{{ $credit->name }}</div>
                                                <div class="text-muted small">
                                                    {{ __('pages/customers_info.passport_prefix') }}
                                                    {{ $credit->passport ?? '-' }}
                                                </div>
                                            </td>

                                            <td>{{ $credit->phone }}</td>

                                            <td class="branch">
                                                <div class="fw-medium">{{ $credit->branch }}</div>
                                                <div class="text-muted small">
                                                    {{ $credit->clientref ?? '-' }}
                                                </div>
                                            </td>

                                            {{-- <td>{{ $credit->contract }}</td> --}}

                                            <td class="contract">
                                                <div class="fw-medium">{{ $credit->contract }}</div>
                                                <div class="text-muted small">
                                                    {{ $credit->date_ ? \Carbon\Carbon::parse($credit->date_)->format('Y-m-d') : '-' }}
                                                </div>
                                            </td>

                                            {{-- Amount --}}
                                            <td class="text-end">
                                                <div
                                                    class="fw-medium {{ $credit->amount_local !== null ? 'text-primary' : '' }}">
                                                    {{ number_format((float) ($credit->amount_local ?? ($credit->amount ?? 0)), 2) }}
                                                </div>

                                                @if ($credit->amount_local !== null)
                                                    <div class="small text-muted">
                                                        {{ __('pages/customers_info.center_prefix') }}
                                                        {{ number_format((float) ($credit->amount ?? 0), 2) }}
                                                    </div>
                                                @endif
                                            </td>

                                            {{-- Paid --}}
                                            <td class="text-end">
                                                <div
                                                    class="fw-medium {{ $credit->paid_local !== null ? 'text-primary' : '' }}">
                                                    {{ number_format((float) ($credit->paid_local ?? ($credit->paid ?? 0)), 2) }}
                                                </div>

                                                @if ($credit->paid_local !== null)
                                                    <div class="small text-muted">
                                                        {{ __('pages/customers_info.center_prefix') }}
                                                        {{ number_format((float) ($credit->paid ?? 0), 2) }}
                                                    </div>
                                                @endif
                                            </td>

                                            {{-- Local Remaining --}}
                                            <td class="text-end">
                                                @if ($credit->local_remaining === null)
                                                    <span class="badge bg-warning-subtle text-warning">
                                                        {{ __('pages/customers_info.local_missing') }}
                                                    </span>
                                                @else
                                                    <div class="fw-medium text-primary">
                                                        {{ number_format($credit->local_remaining, 2) }}
                                                    </div>
                                                @endif
                                            </td>

                                            <td>
                                                <span
                                                    class="badge {{ !empty($credit->active) ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                                    {{ !empty($credit->active) ? __('pages/customers_info.state_active') : __('pages/customers_info.state_blocked') }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">
                                                {{ __('pages/customers_info.no_records') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted small">
                            {{ __('pages/customers_info.total') }}: {{ $credit_users_info->total() }} |
                            {{ __('pages/customers_info.page') }}: {{ $credit_users_info->currentPage() }} /
                            {{ $credit_users_info->lastPage() }}
                        </div>
                        <div>
                            {{ $credit_users_info->onEachSide(1)->links('vendor.pagination.custom') }}
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- RIGHT: DETAIL --}}
        <div class="col-xxl-3">
            <div class="card" id="contact-view-detail">

                <div class="card-body text-center">
                    <div class="position-relative d-inline-block">
                        <img src="{{ URL::asset('build/images/users/user.jpg') }}" alt=""
                            class="avatar-lg rounded-circle img-thumbnail">
                        <span class="contact-active position-absolute rounded-circle bg-success">
                            <span class="visually-hidden"></span>
                        </span>
                    </div>

                    <h5 class="mt-4 mb-1" id="d_name">{{ $selected->name ?? '-' }}</h5>
                    <p class="text-muted" id="d_branch">{{ $selected->branch ?? '-' }}</p>
                </div>

                <div class="card-body">
                    <h6 class="text-muted text-uppercase fw-semibold mb-3">
                        {{ __('pages/customers_info.credit_information') }}</h6>

                    <p class="text-muted mb-4" id="d_note">
                        {{ $selected->note ?? '—' }}
                    </p>

                    <div class="table-responsive table-card">
                        <table class="table table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="fw-medium">{{ __('pages/customers_info.client_ref') }}</td>
                                    <td id="d_clientref">{{ $selected->clientref ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">{{ __('pages/customers_info.passport') }}</td>
                                    <td id="d_passport">{{ $selected->passport ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">{{ __('pages/customers_info.phone') }}</td>
                                    <td id="d_phone">{{ $selected->phone ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">{{ __('pages/customers_info.contract') }}</td>
                                    <td id="d_contract">{{ $selected->contract ?? '-' }}</td>
                                </tr>

                                {{-- Amount: Local + Center --}}
                                @php
                                    $selAmountLocal = $selected->amount_local ?? null;
                                    $selAmountCenter = $selected->amount ?? null;

                                    $selPaidLocal = $selected->paid_local ?? null;
                                    $selPaidCenter = $selected->paid ?? null;
                                @endphp

                                <tr>
                                    <td class="fw-medium">{{ __('pages/customers_info.amount') }}</td>
                                    <td>
                                        <div id="d_amount_local">
                                            @if ($selected)
                                                {{ $selAmountLocal !== null ? number_format((float) $selAmountLocal, 2) : ($selAmountCenter !== null ? number_format((float) $selAmountCenter, 2) : '-') }}
                                            @else
                                                -
                                            @endif
                                        </div>
                                        <div class="small text-muted" id="d_amount_center">
                                            @if ($selected && $selAmountLocal !== null && $selAmountCenter !== null)
                                                {{ __('pages/customers_info.center_prefix') }}
                                                {{ number_format((float) $selAmountCenter, 2) }}
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="fw-medium">{{ __('pages/customers_info.paid') }}</td>
                                    <td>
                                        <div id="d_paid_local">
                                            @if ($selected)
                                                {{ $selPaidLocal !== null ? number_format((float) $selPaidLocal, 2) : ($selPaidCenter !== null ? number_format((float) $selPaidCenter, 2) : '-') }}
                                            @else
                                                -
                                            @endif
                                        </div>
                                        <div class="small text-muted" id="d_paid_center">
                                            @if ($selected && $selPaidLocal !== null && $selPaidCenter !== null)
                                                {{ __('pages/customers_info.center_prefix') }}
                                                {{ number_format((float) $selPaidCenter, 2) }}
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="fw-medium">{{ __('pages/customers_info.will_pay_date') }}</td>
                                    <td id="d_willpaiddate">
                                        {{ $selected ? optional($selected->willpaiddate)->format('Y-m-d') ?? '-' : '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">{{ __('pages/customers_info.status') }}</td>
                                    <td id="d_status">{{ $selected->status ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">{{ __('pages/customers_info.active') }}</td>
                                    <td id="d_active">
                                        {{ $selected ? (!empty($selected->active) ? __('pages/customers_info.state_active') : __('pages/customers_info.state_blocked')) : '-' }}
                                </tr>
                                <tr>
                                    <td class="fw-medium">{{ __('pages/customers_info.last_note_date') }}</td>
                                    <td id="d_lastnoteddate">
                                        {{ $selected ? optional($selected->lastnoteddate)->format('Y-m-d H:i:s') ?? '-' : '-' }}
                                    </td>
                                </tr>
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
        window.i18n = {
            centerPrefix: @json(__('pages/customers_info.center_prefix')),
        };
    </script>
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>

    <script>
        (function() {
            const table = document.getElementById('customerTable');
            if (!table) return;

            let activeRow = table.querySelector('.js-credit-row.table-active');

            const setText = (id, value, dash = '-') => {
                const el = document.getElementById(id);
                if (!el) return;
                const v = (value ?? '').toString().trim();
                el.textContent = v !== '' ? v : dash;
            };

            const setNote = (value) => {
                const el = document.getElementById('d_note');
                if (!el) return;
                const v = (value ?? '').toString().trim();
                el.textContent = v !== '' ? v : '—';
            };

            const setCenterLine = (id, prefix, value) => {
                const el = document.getElementById(id);
                if (!el) return;
                const v = (value ?? '').toString().trim();
                el.textContent = v !== '' ? (prefix + v) : '';
            };

            const fillDetail = (row) => {
                const d = row.dataset;

                setText('d_name', d.name);
                setText('d_branch', d.branch);
                setText('d_clientref', d.clientref);
                setText('d_passport', d.passport);
                setText('d_phone', d.phone);
                setText('d_contract', d.contract);

                // Amount Local + Center
                // const amountLocal = d.amountLocal || '';
                // const amountCenter = d.amountCenter || '';
                // setText('d_amount_local', amountLocal !== '' ? amountLocal : amountCenter);
                // setCenterLine('d_amount_center', 'Merkez: ', (amountLocal !== '' ? amountCenter : ''));

                // // Paid Local + Center
                // const paidLocal = d.paidLocal || '';
                // const paidCenter = d.paidCenter || '';
                // setText('d_paid_local', paidLocal !== '' ? paidLocal : paidCenter);
                // setCenterLine('d_paid_center', 'Merkez: ', (paidLocal !== '' ? paidCenter : ''));

                // Amount Local + Center
                const amountLocal = d.amountLocal || '';
                const amountCenter = d.amountCenter || '';
                setText('d_amount_local', amountLocal !== '' ? amountLocal : amountCenter);
                setCenterLine('d_amount_center', window.i18n.centerPrefix, (amountLocal !== '' ? amountCenter :
                    ''));

                // Paid Local + Center
                const paidLocal = d.paidLocal || '';
                const paidCenter = d.paidCenter || '';
                setText('d_paid_local', paidLocal !== '' ? paidLocal : paidCenter);
                setCenterLine('d_paid_center', window.i18n.centerPrefix, (paidLocal !== '' ? paidCenter : ''));


                setText('d_willpaiddate', d.willpaiddate);
                setText('d_status', d.status);
                setText('d_active', d.active);
                setText('d_lastnoteddate', d.lastnoteddate);

                setNote(d.note);
            };

            table.addEventListener('click', function(e) {
                // checkbox'a tıklayınca satır seçimini istemiyorsan aç:
                if (e.target && e.target.classList.contains('js-row-check')) return;

                const row = e.target.closest('.js-credit-row');
                if (!row) return;

                if (activeRow) activeRow.classList.remove('table-active');
                row.classList.add('table-active');
                activeRow = row;

                fillDetail(row);

                // URL’e id yaz (refresh’te aynı müşteri kalsın)
                const url = new URL(window.location.href);
                url.searchParams.set('id', row.dataset.id);
                history.replaceState(null, '', url.toString());
            });

            // Sayfa açılınca seçili yoksa ilk satırı seç
            if (!activeRow) {
                const firstRow = table.querySelector('.js-credit-row');
                if (firstRow) {
                    firstRow.classList.add('table-active');
                    activeRow = firstRow;
                    fillDetail(firstRow);
                }
            }

            const selectedCountEl = document.getElementById('selectedCount');
            const goPaymentBtn = document.getElementById('goPaymentBtn');

            const refreshSelectionUI = () => {
                const checks = Array.from(table.querySelectorAll('.js-row-check'));
                const selected = checks.filter(c => c.checked).length;

                if (selectedCountEl) selectedCountEl.textContent = selected.toString();
                if (goPaymentBtn) goPaymentBtn.disabled = selected === 0;
            };

            table.addEventListener('change', function(e) {
                if (!e.target.classList.contains('js-row-check')) return;
                refreshSelectionUI();
            });

            // ilk yüklemede
            refreshSelectionUI();

        })();
    </script>
@endsection
