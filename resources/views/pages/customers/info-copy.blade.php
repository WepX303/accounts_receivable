@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row">

        <div class="col-xxl-9">
            <div class="card" id="contactList">
                <div class="card-header">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-6">
                            <form method="GET" action="{{ route('customers.info') }}">
                                <div class="search-box">
                                    <input type="text" name="q" class="form-control"
                                        placeholder="Search name / phone / passport / contract / clientref..."
                                        value="{{ $q ?? request('q') }}">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive table-card mb-3">
                        <table class="table align-middle table-nowrap mb-0" id="customerTable">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" style="width: 50px;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="checkAll" value="option">
                                        </div>
                                    </th>

                                    <th scope="col">Name</th>
                                    {{-- <th scope="col">Passport</th> --}}
                                    <th scope="col">Phone</th>
                                    <th scope="col">Branch</th>
                                    <th scope="col">Contract</th>
                                    {{-- <th scope="col">Client Ref</th> --}}
                                    <th scope="col" class="text-end">Amount</th>
                                    <th scope="col" class="text-end">Paid</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>

                            <tbody class="list form-check-all">
                                @forelse($credit_users_info as $credit)
                                    @php
                                        // seçili: logicalref üzerinden (controller ile uyumlu)
                                        $isActive =
                                            isset($selected) &&
                                            $selected &&
                                            (int) $selected->logicalref === (int) $credit->logicalref;

                                        $amountText =
                                            $credit->amount_local ??
                                            (isset($credit->amount) ? number_format((float) $credit->amount, 2) : '');
                                        $paidText = $credit->paid_local ?? ($credit->paid ?? '');

                                        $willPayText = optional($credit->willpaiddate)->format('Y-m-d');
                                        $willPayText = $willPayText ?? (string) ($credit->willpaiddate ?? '');

                                        $lastNoteText = (string) ($credit->lastnoteddate ?? '');
                                    @endphp

                                    <tr class="js-credit-row {{ $isActive ? 'table-active' : '' }}" style="cursor:pointer;"
                                        data-id="{{ $credit->logicalref }}" data-name="{{ e($credit->name ?? '') }}"
                                        data-branch="{{ e($credit->branch ?? '') }}"
                                        data-passport="{{ e($credit->passport ?? '') }}"
                                        data-phone="{{ e($credit->phone ?? '') }}"
                                        data-contract="{{ e($credit->contract ?? '') }}"
                                        data-clientref="{{ e($credit->clientref ?? '') }}"
                                        data-amount="{{ e($amountText) }}" data-paid="{{ e($paidText) }}"
                                        data-willpaiddate="{{ e($willPayText) }}"
                                        data-status="{{ e($credit->status ?? '') }}"
                                        data-active="{{ !empty($credit->active) ? 'Active' : 'Blok' }}"
                                        data-note="{{ e($credit->note ?? '') }}"
                                        data-lastnoteddate="{{ e($lastNoteText) }}">
                                        <th scope="row">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="chk_child"
                                                    value="option1">
                                            </div>
                                        </th>

                                        <td class="customer_name">
                                            <div class="fw-medium">{{ $credit->name }}</div>
                                            <div class="text-muted small">
                                                Passport: {{ $credit->passport ?? '-' }}
                                            </div>
                                        </td>
                                        {{-- <td>{{ $credit->passport }}</td> --}}
                                        <td>{{ $credit->phone }}</td>
                                        {{-- <td>{{ $credit->branch }}</td> --}}


                                        <td class="branch">
                                            <div class="fw-medium">{{ $credit->branch }}</div>
                                            <div class="text-muted small">
                                                {{ $credit->clientref ?? '-' }}
                                            </div>
                                        </td>

                                        <td>{{ $credit->contract }}</td>
                                        {{-- Amount --}}
                                        <td class="text-end">
                                            <div
                                                class="fw-medium {{ $credit->amount_local !== null ? 'text-primary' : '' }}">
                                                {{ number_format($credit->amount_local ?? ($credit->amount ?? 0), 2) }}
                                            </div>

                                            @if ($credit->amount_local !== null)
                                                <div class="small text-muted">
                                                    Merkez: {{ number_format($credit->amount ?? 0, 2) }}
                                                </div>
                                            @endif
                                        </td>
                                        {{-- Paid --}}
                                        <td class="text-end">
                                            <div
                                                class="fw-medium {{ $credit->paid_local !== null ? 'text-primary' : '' }}">
                                                {{ number_format($credit->paid_local ?? ($credit->paid ?? 0), 2) }}
                                            </div>

                                            @if ($credit->paid_local !== null)
                                                <div class="small text-muted">
                                                    Merkez: {{ number_format($credit->paid ?? 0, 2) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <span
                                                class="badge {{ !empty($credit->active) ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                                {{ !empty($credit->active) ? 'Active' : 'Blok' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center text-muted py-4">
                                            No records found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted small">
                            Toplam: {{ $credit_users_info->total() }} |
                            Sayfa: {{ $credit_users_info->currentPage() }} / {{ $credit_users_info->lastPage() }}
                        </div>
                        <div>
                            {{ $credit_users_info->onEachSide(1)->links('vendor.pagination.custom') }}
                        </div>
                    </div>

                </div>
            </div>
        </div>

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

                    <ul class="list-inline mb-0">
                        <li class="list-inline-item avatar-xs">
                            <a href="javascript:void(0);" class="avatar-title bg-success-subtle text-success fs-15 rounded">
                                <i class="ri-phone-line"></i>
                            </a>
                        </li>
                        <li class="list-inline-item avatar-xs">
                            <a href="javascript:void(0);" class="avatar-title bg-danger-subtle text-danger fs-15 rounded">
                                <i class="ri-mail-line"></i>
                            </a>
                        </li>
                        <li class="list-inline-item avatar-xs">
                            <a href="javascript:void(0);"
                                class="avatar-title bg-warning-subtle text-warning fs-15 rounded">
                                <i class="ri-question-answer-line"></i>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    <h6 class="text-muted text-uppercase fw-semibold mb-3">Credit Information</h6>

                    <p class="text-muted mb-4" id="d_note">
                        {{ $selected->note ?? '—' }}
                    </p>

                    <div class="table-responsive table-card">
                        <table class="table table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="fw-medium">Client Ref</td>
                                    <td id="d_clientref">{{ $selected->clientref ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">Passport</td>
                                    <td id="d_passport">{{ $selected->passport ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">Phone</td>
                                    <td id="d_phone">{{ $selected->phone ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">Contract</td>
                                    <td id="d_contract">{{ $selected->contract ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">Amount</td>
                                    <td id="d_amount">
                                        {{ $selected->amount_local ?? (isset($selected->amount) ? number_format((float) $selected->amount, 2) : '-') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">Paid</td>
                                    <td id="d_paid">{{ $selected->paid_local ?? ($selected->paid ?? '-') }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">Will Pay Date</td>
                                    <td id="d_willpaiddate">
                                        {{ optional($selected->willpaiddate ?? null)->format('Y-m-d') ?? ($selected->willpaiddate ?? '-') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">Status</td>
                                    <td id="d_status">{{ $selected->status ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">Active</td>
                                    <td id="d_active">
                                        {{ isset($selected) ? (!empty($selected->active) ? 'Active' : 'Blok') : '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">Last Note Date</td>
                                    <td id="d_lastnoteddate">{{ $selected->lastnoteddate ?? '-' }}</td>
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
    <script src="{{ URL::asset('build/libs/list.js/list.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>

    <script>
        (function() {
            const table = document.getElementById('customerTable');
            if (!table) return;

            let activeRow = table.querySelector('.js-credit-row.table-active');

            const setText = (id, value) => {
                const el = document.getElementById(id);
                if (!el) return;

                const v = (value ?? '').toString().trim();
                el.textContent = v !== '' ? v : '-';
            };

            const setNote = (value) => {
                const el = document.getElementById('d_note');
                if (!el) return;

                const v = (value ?? '').toString().trim();
                el.textContent = v !== '' ? v : '—';
            };

            const fillDetail = (row) => {
                const d = row.dataset;

                setText('d_name', d.name);
                setText('d_branch', d.branch);
                setText('d_clientref', d.clientref);
                setText('d_passport', d.passport);
                setText('d_phone', d.phone);
                setText('d_contract', d.contract);
                setText('d_amount', d.amount);
                setText('d_paid', d.paid);
                setText('d_willpaiddate', d.willpaiddate);
                setText('d_status', d.status);
                setText('d_active', d.active);
                setText('d_lastnoteddate', d.lastnoteddate);
                setNote(d.note);
            };

            table.addEventListener('click', function(e) {
                const row = e.target.closest('.js-credit-row');
                if (!row) return;

                if (activeRow) activeRow.classList.remove('table-active');
                row.classList.add('table-active');
                activeRow = row;

                fillDetail(row);

                // Opsiyonel: URL’e id yaz (refresh’te aynı müşteri kalsın)
                const url = new URL(window.location.href);
                url.searchParams.set('id', row.dataset.id);
                history.replaceState(null, '', url.toString());
            });

            // ✅ Sayfa açılınca seçili yoksa ilk satırı otomatik seç
            if (!activeRow) {
                const firstRow = table.querySelector('.js-credit-row');
                if (firstRow) {
                    firstRow.classList.add('table-active');
                    activeRow = firstRow;
                    fillDetail(firstRow);
                }
            }
        })();
    </script>
@endsection
