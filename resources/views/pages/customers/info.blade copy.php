@extends('layouts.layouts-horizontal')

@section('content')

    <div class="row">
        
        <div class="col-xxl-9">
            <div class="card" id="contactList">
                <div class="card-header">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="search-box">
                                <input type="text" class="form-control search"
                                    placeholder="Search for contact...">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div>
                        <div class="table-responsive table-card mb-3">
                            <table class="table align-middle table-nowrap mb-0" id="customerTable">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="width: 50px;">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    id="checkAll" value="option">
                                            </div>
                                        </th>
                                        <th class="sort" data-sort="name" scope="col">Name</th>
                                        <th class="sort" data-sort="company_name" scope="col">Company
                                        </th>
                                        <th class="sort" data-sort="designation" scope="col">Designation
                                        </th>
                                        <th class="sort" data-sort="email_id" scope="col">Email ID</th>
                                        <th class="sort" data-sort="phone" scope="col">Phone No</th>
                                        <th class="sort" data-sort="lead_score" scope="col">Lead Score</th>
                                        <th class="sort" data-sort="tags" scope="col">Tags</th>
                                        <th class="sort" data-sort="date" scope="col">Last Contacted</th>
                                    </tr>
                                </thead>
                                <tbody class="list form-check-all">
                                    <tr>
                                        <th scope="row">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="chk_child" value="option1">
                                            </div>
                                        </th>
                                        <td class="id" style="display:none;"><a href="javascript:void(0);" class="fw-medium link-primary">#VZ001</a></td>
                                       
                                        <td class="name">Tonya Noble</td>
                                        <td class="company_name">Nesta Technologies</td>
                                        <td class="designation">Lead Designer / Developer</td>
                                        <td class="email_id">tonyanoble@velzon.com</td>
                                        <td class="phone">414-453-5725</td>
                                        <td class="lead_score">154</td>
                                        <td class="tags">
                                            <span class="badge bg-primary-subtle text-primary">Lead</span>
                                            <span class="badge bg-primary-subtle text-primary">Partner</span>
                                        </td>
                                        <td class="date">15 Dec, 2021 <small class="text-muted">08:58AM</small></td>                                       
                                    </tr>
                                </tbody>
                            </table>
                           
                        </div>
                        
                    </div>
                    

                </div>
            </div>
            <!--end card-->
        </div>
        <!--end col-->
        <div class="col-xxl-3">
            <div class="card" id="contact-view-detail">
                <div class="card-body text-center">
                    <div class="position-relative d-inline-block">
                        <img src="{{ URL::asset('build/images/users/user.jpg') }}" alt=""
                            class="avatar-lg rounded-circle img-thumbnail">
                        <span class="contact-active position-absolute rounded-circle bg-success"><span
                                class="visually-hidden"></span>
                    </div>
                    <h5 class="mt-4 mb-1">Tonya Noble</h5>
                    <p class="text-muted">Nesta Technologies</p>

                    <ul class="list-inline mb-0">
                        <li class="list-inline-item avatar-xs">
                            <a href="javascript:void(0);"
                                class="avatar-title bg-success-subtle text-success fs-15 rounded">
                                <i class="ri-phone-line"></i>
                            </a>
                        </li>
                        <li class="list-inline-item avatar-xs">
                            <a href="javascript:void(0);"
                                class="avatar-title bg-danger-subtle text-danger fs-15 rounded">
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
                    <h6 class="text-muted text-uppercase fw-semibold mb-3">Personal Information</h6>
                    <p class="text-muted mb-4">Hello, I'm Tonya Noble, The most effective objective is
                        one that is tailored to the job you are applying for. It states what kind of
                        career you are seeking, and what skills and experiences.</p>
                    <div class="table-responsive table-card">
                        <table class="table table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="fw-medium" scope="row">Designation</td>
                                    <td>Lead Designer / Developer</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium" scope="row">Email ID</td>
                                    <td>tonyanoble@velzon.com</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium" scope="row">Phone No</td>
                                    <td>414-453-5725</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium" scope="row">Lead Score</td>
                                    <td>154</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium" scope="row">Tags</td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary">Lead</span>
                                        <span class="badge bg-primary-subtle text-primary">Partner</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-medium" scope="row">Last Contacted</td>
                                    <td>15 Dec, 2021 <small class="text-muted">08:58AM</small></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!--end card-->
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


















{{-- @extends('layouts.layouts-horizontal')

@section('content')

    <div class="row">
        
        <div class="col-xxl-9">
            <div class="card" id="contactList">
                <div class="card-header">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="search-box">
                                <input type="text" class="form-control search"
                                    placeholder="Search for contact...">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div>
                        <div class="table-responsive table-card mb-3">
                            <table class="table align-middle table-nowrap mb-0" id="customerTable">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="width: 50px;">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    id="checkAll" value="option">
                                            </div>
                                        </th>
                                        <th class="sort" data-sort="name" scope="col">Name</th>
                                        <th class="sort" data-sort="company_name" scope="col">Company
                                        </th>
                                        <th class="sort" data-sort="designation" scope="col">Designation
                                        </th>
                                        <th class="sort" data-sort="email_id" scope="col">Email ID</th>
                                        <th class="sort" data-sort="phone" scope="col">Phone No</th>
                                        <th class="sort" data-sort="lead_score" scope="col">Lead Score</th>
                                        <th class="sort" data-sort="tags" scope="col">Tags</th>
                                        <th class="sort" data-sort="date" scope="col">Last Contacted</th>
                                    </tr>
                                </thead>
                                <tbody class="list form-check-all">
                                    <tr>
                                        <th scope="row">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="chk_child" value="option1">
                                            </div>
                                        </th>
                                        <td class="id" style="display:none;"><a href="javascript:void(0);" class="fw-medium link-primary">#VZ001</a></td>
                                       
                                        <td class="name">Tonya Noble</td>
                                        <td class="company_name">Nesta Technologies</td>
                                        <td class="designation">Lead Designer / Developer</td>
                                        <td class="email_id">tonyanoble@velzon.com</td>
                                        <td class="phone">414-453-5725</td>
                                        <td class="lead_score">154</td>
                                        <td class="tags">
                                            <span class="badge bg-primary-subtle text-primary">Lead</span>
                                            <span class="badge bg-primary-subtle text-primary">Partner</span>
                                        </td>
                                        <td class="date">15 Dec, 2021 <small class="text-muted">08:58AM</small></td>                                       
                                    </tr>
                                </tbody>
                            </table>
                           
                        </div>
                        
                    </div>
                    

                </div>
            </div>
            <!--end card-->
        </div>
        <!--end col-->
        <div class="col-xxl-3">
            <div class="card" id="contact-view-detail">
                <div class="card-body text-center">
                    <div class="position-relative d-inline-block">
                        <img src="{{ URL::asset('build/images/users/user.jpg') }}" alt=""
                            class="avatar-lg rounded-circle img-thumbnail">
                        <span class="contact-active position-absolute rounded-circle bg-success"><span
                                class="visually-hidden"></span>
                    </div>
                    <h5 class="mt-4 mb-1">Tonya Noble</h5>
                    <p class="text-muted">Nesta Technologies</p>

                    <ul class="list-inline mb-0">
                        <li class="list-inline-item avatar-xs">
                            <a href="javascript:void(0);"
                                class="avatar-title bg-success-subtle text-success fs-15 rounded">
                                <i class="ri-phone-line"></i>
                            </a>
                        </li>
                        <li class="list-inline-item avatar-xs">
                            <a href="javascript:void(0);"
                                class="avatar-title bg-danger-subtle text-danger fs-15 rounded">
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
                    <h6 class="text-muted text-uppercase fw-semibold mb-3">Personal Information</h6>
                    <p class="text-muted mb-4">Hello, I'm Tonya Noble, The most effective objective is
                        one that is tailored to the job you are applying for. It states what kind of
                        career you are seeking, and what skills and experiences.</p>
                    <div class="table-responsive table-card">
                        <table class="table table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="fw-medium" scope="row">Designation</td>
                                    <td>Lead Designer / Developer</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium" scope="row">Email ID</td>
                                    <td>tonyanoble@velzon.com</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium" scope="row">Phone No</td>
                                    <td>414-453-5725</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium" scope="row">Lead Score</td>
                                    <td>154</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium" scope="row">Tags</td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary">Lead</span>
                                        <span class="badge bg-primary-subtle text-primary">Partner</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-medium" scope="row">Last Contacted</td>
                                    <td>15 Dec, 2021 <small class="text-muted">08:58AM</small></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!--end card-->
        </div>
        <!--end col-->
    </div>
    <!--end row-->
@endsection
@section('script')
    <script src="{{ URL::asset('build/libs/list.js/list.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection --}}


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
                                <input
                                    type="text"
                                    name="q"
                                    class="form-control"
                                    placeholder="Search by name / phone / passport / clientref..."
                                    value="{{ request('q') }}"
                                >
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </form>
                    </div>

                    <div class="col-md-6 text-end">
                        <div class="text-muted">
                            Total in this page: <b>{{ $credits->count() }}</b> /
                            All: <b>{{ $credits->total() }}</b>
                        </div>
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
                                        <input class="form-check-input" type="checkbox" id="checkAll">
                                    </div>
                                </th>

                                <th scope="col">Name</th>
                                <th scope="col">Branch</th>
                                <th scope="col">Passport</th>
                                <th scope="col">Phone</th>
                                <th scope="col">Contract</th>
                                <th scope="col" class="text-end">Amount</th>
                                <th scope="col" class="text-end">Paid</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($credits as $credit)
                                @php
                                    $isActive = isset($selected) && $selected && $selected->id === $credit->id;

                                    // Amount gösterimi (dataset için string)
                                    $amountText = $credit->amount_local ?? (isset($credit->amount) ? number_format((float)$credit->amount, 2) : '');
                                    $paidText   = $credit->paid_local ?? ($credit->paid ?? '');

                                    $dateText = optional($credit->date_)->format('Y-m-d');
                                    $dateText = $dateText ?? (string)($credit->date_ ?? '');

                                    $willPayText = optional($credit->willpaiddate)->format('Y-m-d');
                                    $willPayText = $willPayText ?? (string)($credit->willpaiddate ?? '');
                                @endphp

                                <tr
                                    class="js-credit-row {{ $isActive ? 'table-active' : '' }}"
                                    style="cursor:pointer;"
                                    data-id="{{ $credit->id }}"
                                    data-name="{{ e($credit->name ?? '') }}"
                                    data-branch="{{ e($credit->branch ?? '') }}"
                                    data-passport="{{ e($credit->passport ?? '') }}"
                                    data-phone="{{ e($credit->phone ?? '') }}"
                                    data-contract="{{ e($credit->contract ?? '') }}"
                                    data-clientref="{{ e($credit->clientref ?? '') }}"
                                    data-date="{{ e($dateText) }}"
                                    data-amount="{{ e($amountText) }}"
                                    data-paid="{{ e($paidText) }}"
                                    data-willpaiddate="{{ e($willPayText) }}"
                                    data-status="{{ e($credit->status ?? '') }}"
                                    data-active="{{ !empty($credit->active) ? '1' : '0' }}"
                                    data-note="{{ e($credit->note ?? '') }}"
                                    data-lastnoteddate="{{ e($credit->lastnoteddate ?? '') }}"
                                >
                                    <th scope="row">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="chk_child">
                                        </div>
                                    </th>

                                    <td class="fw-medium">
                                        {{ $credit->name }}
                                        <div class="text-muted small">{{ $credit->clientref }}</div>
                                    </td>

                                    <td>{{ $credit->branch }}</td>
                                    <td>{{ $credit->passport }}</td>
                                    <td>{{ $credit->phone }}</td>
                                    <td>{{ $credit->contract }}</td>

                                    <td class="text-end">
                                        {{ $credit->amount_local ?? (isset($credit->amount) ? number_format((float)$credit->amount, 2) : '-') }}
                                    </td>

                                    <td class="text-end">
                                        {{ $credit->paid_local ?? ($credit->paid ?? '-') }}
                                    </td>

                                    <td>
                                        <span class="badge bg-primary-subtle text-primary">
                                            {{ $credit->status ?? 'EMPTY' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
                                        No records found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted small">
                        Toplam: {{ $credits->total() }} |
                        Sayfa: {{ $credits->currentPage() }} / {{ $credits->lastPage() }}
                    </div>
                    <div>
                        {{ $credits->appends(request()->query())->onEachSide(1)->links('vendor.pagination.custom') }}
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
                        <a href="javascript:void(0);" class="avatar-title bg-warning-subtle text-warning fs-15 rounded">
                            <i class="ri-question-answer-line"></i>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body">
                <h6 class="text-muted text-uppercase fw-semibold mb-3">Credit Information</h6>

                <p class="text-muted mb-4" id="d_note">
                    {{ $selected->note ?? 'Select a row to see details.' }}
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
                                <td id="d_amount">{{ $selected->amount_local ?? ($selected->amount ?? '-') }}</td>
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
                                <td id="d_active">{{ isset($selected) ? (!empty($selected->active) ? 'YES' : 'NO') : '-' }}</td>
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
    {{-- Sweetalert2 bu sayfada kullanılmıyorsa kaldırabilirsin --}}
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>

    <script>
        (function () {
            const table = document.getElementById('customerTable');
            if (!table) return;

            let activeRow = table.querySelector('.js-credit-row.table-active');

            const setText = (id, value) => {
                const el = document.getElementById(id);
                if (!el) return;

                const v = (value ?? '').toString().trim();
                el.textContent = v !== '' ? v : '-';
            };

            table.addEventListener('click', function (e) {
                const row = e.target.closest('.js-credit-row');
                if (!row) return;

                if (activeRow) activeRow.classList.remove('table-active');
                row.classList.add('table-active');
                activeRow = row;

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
                setText('d_active', d.active === '1' ? 'YES' : 'NO');
                setText('d_lastnoteddate', d.lastnoteddate);

                const noteEl = document.getElementById('d_note');
                if (noteEl) {
                    const note = (d.note ?? '').toString().trim();
                    noteEl.textContent = note !== '' ? note : '—';
                }
            });
        })();
    </script>
@endsection
