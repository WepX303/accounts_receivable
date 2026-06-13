@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-1">{{ __('messages.sms_distribution') }}</h4>
            <p class="text-muted mb-0">
                {{ __('messages.sms_distribution_desc') }}
            </p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('sms_skipped_no_phone') !== null)
        <div class="alert alert-warning">
            {{ __('messages.skipped_no_phone_count') }}: {{ session('sms_skipped_no_phone') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">

            {{-- FILTER CARD --}}
            {{-- <div class="card mb-4">
                <div class="card-body border border-dashed border-end-0 border-start-0">
                    <form method="GET" action="{{ route('sms.index') }}">
                        <div class="row g-3 align-items-end">

                            <div class="col-xxl-2 col-xl-3 col-lg-4 col-md-6 col-12">
                                <label class="form-label">{{ __('messages.branch') }}</label>
                                <select name="branch" class="form-select">
                                    <option value="">{{ __('messages.all') }}</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch }}"
                                            {{ request('branch') == $branch ? 'selected' : '' }}>
                                            {{ $branch }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-12">
                                <label class="form-label">{{ __('messages.customer_name') }}</label>
                                <input type="text" name="name" class="form-control" value="{{ request('name') }}">
                            </div>

                            <div class="col-xxl-2 col-xl-3 col-lg-4 col-md-6 col-12">
                                <label class="form-label">{{ __('messages.phone') }}</label>
                                <input type="text" name="phone" class="form-control" value="{{ request('phone') }}">
                            </div>

                            <div class="col-xxl-2 col-xl-3 col-lg-4 col-md-6 col-12">
                                <label class="form-label">{{ __('messages.passport') }}</label>
                                <input type="text" name="passport" class="form-control"
                                    value="{{ request('passport') }}">
                            </div>

                            <div class="col-xxl-2 col-xl-3 col-lg-4 col-md-6 col-12">
                                <label class="form-label">{{ __('messages.contract') }}</label>
                                <input type="text" name="contract" class="form-control"
                                    value="{{ request('contract') }}">
                            </div>

                            <div class="col-xxl-2 col-xl-3 col-lg-4 col-md-6 col-12">
                                <label class="form-label">{{ __('messages.status') }}</label>
                                <select name="status_type" class="form-select">
                                    <option value="all"
                                        {{ $statusType === 'all' || $statusType === '' ? 'selected' : '' }}>
                                        {{ __('messages.all') }}
                                    </option>
                                    <option value="overdue" {{ $statusType === 'overdue' ? 'selected' : '' }}>
                                        {{ __('messages.overdue') }}
                                    </option>
                                    <option value="due_today" {{ $statusType === 'due_today' ? 'selected' : '' }}>
                                        {{ __('messages.due_today') }}
                                    </option>
                                    <option value="due_in_days" {{ $statusType === 'due_in_days' ? 'selected' : '' }}>
                                        {{ __('messages.due_in_days') }}
                                    </option>
                                    <option value="unpaid" {{ $statusType === 'unpaid' ? 'selected' : '' }}>
                                        {{ __('messages.unpaid') }}
                                    </option>
                                </select>
                            </div>

                            <div class="col-xxl-2 col-xl-3 col-lg-4 col-md-6 col-12">
                                <label class="form-label">{{ __('messages.due_days') }}</label>
                                <input type="number" min="1" name="due_days" class="form-control"
                                    value="{{ request('due_days', 3) }}">
                            </div>

                            <div class="col-xxl-2 col-xl-3 col-lg-4 col-md-6 col-12">
                                <label class="form-label">{{ __('messages.min_remaining') }}</label>
                                <input type="number" step="0.01" min="0" name="min_remaining"
                                    class="form-control" value="{{ request('min_remaining') }}">
                            </div>

                            <div class="col-xxl-2 col-xl-3 col-lg-4 col-md-6 col-12">
                                <label class="form-label">{{ __('messages.max_remaining') }}</label>
                                <input type="number" step="0.01" min="0" name="max_remaining"
                                    class="form-control" value="{{ request('max_remaining') }}">
                            </div>

                            <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-12 col-12 d-flex gap-2">
                                <button type="submit"
                                    class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-1">
                                    {{ __('messages.filter') }}
                                </button>

                                <a href="{{ route('sms.index') }}"
                                    class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-1">
                                    {{ __('messages.reset') }}
                                </a>
                            </div>

                        </div>
                    </form>
                </div>
            </div> --}}

            {{-- FILTER CARD --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        {{ __('messages.filter') }}
                    </h5>
                </div>

                <div class="card-body">
                    <form method="GET" action="{{ route('sms.index') }}">
                        <div class="row g-3">

                            {{-- Main filters --}}
                            <div class="col-xl-3 col-md-6">
                                <label class="form-label">{{ __('messages.branch') }}</label>
                                <select name="branch" class="form-select">
                                    <option value="">{{ __('messages.all') }}</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch }}" @selected(request('branch') == $branch)>
                                            {{ $branch }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-xl-3 col-md-6">
                                <label class="form-label">{{ __('messages.status') }}</label>
                                <select name="status_type" class="form-select">
                                    <option value="all" @selected($statusType === 'all' || $statusType === '')>
                                        {{ __('messages.all') }}
                                    </option>
                                    <option value="overdue" @selected($statusType === 'overdue')>
                                        {{ __('messages.overdue') }}
                                    </option>
                                    <option value="due_today" @selected($statusType === 'due_today')>
                                        {{ __('messages.due_today') }}
                                    </option>
                                    <option value="due_in_days" @selected($statusType === 'due_in_days')>
                                        {{ __('messages.due_in_days') }}
                                    </option>
                                    <option value="unpaid" @selected($statusType === 'unpaid')>
                                        {{ __('messages.unpaid') }}
                                    </option>
                                </select>
                            </div>

                            {{-- <div class="col-xl-2 col-md-6">
                                <label class="form-label">{{ __('messages.due_days') }}</label>
                                <input type="number" min="1" name="due_days" class="form-control"
                                    value="{{ request('due_days', 3) }}">
                            </div> --}}

                            <div class="col-xl-2 col-md-6" id="dueDaysWrapper">
                                <label class="form-label">{{ __('messages.due_days') }}</label>
                                <input type="number" min="1" name="due_days" id="dueDaysInput" class="form-control"
                                    value="{{ request('due_days', 3) }}">
                            </div>

                            <div class="col-xl-4 col-md-6 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="ri-filter-3-line"></i>
                                    {{ __('messages.filter') }}
                                </button>

                                <a href="{{ route('sms.index') }}" class="btn btn-outline-secondary w-100">
                                    <i class="ri-refresh-line"></i>
                                    {{ __('messages.reset') }}
                                </a>
                            </div>

                            <div class="col-12">
                                <hr class="my-2">
                            </div>

                            {{-- Search filters --}}
                            <div class="col-xl-3 col-md-6">
                                <label class="form-label">{{ __('messages.customer_name') }}</label>
                                <input type="text" name="name" class="form-control" value="{{ request('name') }}"
                                    placeholder="{{ __('messages.customer_name') }}">
                            </div>

                            <div class="col-xl-3 col-md-6">
                                <label class="form-label">{{ __('messages.phone') }}</label>
                                <input type="text" name="phone" class="form-control" value="{{ request('phone') }}"
                                    placeholder="{{ __('messages.phone') }}">
                            </div>

                            <div class="col-xl-3 col-md-6">
                                <label class="form-label">{{ __('messages.passport') }}</label>
                                <input type="text" name="passport" class="form-control"
                                    value="{{ request('passport') }}" placeholder="{{ __('messages.passport') }}">
                            </div>

                            <div class="col-xl-3 col-md-6">
                                <label class="form-label">{{ __('messages.contract') }}</label>
                                <input type="text" name="contract" class="form-control"
                                    value="{{ request('contract') }}" placeholder="{{ __('messages.contract') }}">
                            </div>

                            {{-- Amount filters --}}
                            <div class="col-xl-2 col-md-6">
                                <label class="form-label">{{ __('messages.min_remaining') }}</label>
                                <input type="number" step="0.01" min="0" name="min_remaining"
                                    class="form-control" value="{{ request('min_remaining') }}">
                            </div>

                            <div class="col-xl-2 col-md-6">
                                <label class="form-label">{{ __('messages.max_remaining') }}</label>
                                <input type="number" step="0.01" min="0" name="max_remaining"
                                    class="form-control" value="{{ request('max_remaining') }}">
                            </div>

                            <div class="col-xxl-2 col-xl-3 col-lg-4 col-md-6 col-12">
                                <label class="form-label">
                                    {{ __('messages.phone_status') }}
                                </label>

                                <select name="phone_status" class="form-select">
                                    <option value="">
                                        {{ __('messages.all') }}
                                    </option>

                                    <option value="has_phone"
                                        {{ request('phone_status') == 'has_phone' ? 'selected' : '' }}>
                                        {{ __('messages.has_phone') }}
                                    </option>

                                    <option value="no_phone"
                                        {{ request('phone_status') == 'no_phone' ? 'selected' : '' }}>
                                        {{ __('messages.no_phone') }}
                                    </option>

                                    <option value="multi_phone"
                                        {{ request('phone_status') == 'multi_phone' ? 'selected' : '' }}>
                                        {{ __('messages.multi_phone') }}
                                    </option>
                                </select>
                            </div>

                            <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-12">
                                <label class="form-label">
                                    {{ __('messages.payment_date_from') }}
                                </label>

                                <input type="date" name="payment_date_from" class="form-control"
                                    value="{{ request('payment_date_from') }}">
                            </div>

                            <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-12">
                                <label class="form-label">
                                    {{ __('messages.payment_date_to') }}
                                </label>

                                <input type="date" name="payment_date_to" class="form-control"
                                    value="{{ request('payment_date_to') }}">
                            </div>


                        </div>
                    </form>
                </div>
            </div>

            {{-- SELECTION INFO --}}
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <h5 class="mb-1">{{ __('messages.recipient_selection') }}</h5>
                            <p class="text-muted mb-0">
                                {{ __('messages.recipient_selection_desc') }}
                            </p>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary" id="selectedCountBadge">0</span>
                            <span class="text-muted">{{ __('messages.selected_count') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if (!empty($previewRows))
                <div class="card mb-4">
                    <div class="card-body pt-4">
                        {{-- <h5 class="mb-3">{{ __('messages.sms_preview') }}</h5> --}}
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            {{-- <h5 class="mb-0">{{ __('messages.sms_preview') }}</h5> --}}
                            <h5 class="mb-0">
                                {{ __('messages.sms_preview') }}
                                <span class="badge bg-primary ms-2">
                                    {{ count($previewRows) }}
                                </span>
                            </h5>

                            <div class="d-flex gap-2">
                                <a href="{{ route('sms.export.preview', request()->query()) }}"
                                    class="btn btn-success btn-sm">
                                    {{ __('messages.export_excel') }}
                                </a>

                                <form method="POST" action="{{ route('sms.preview.clear', request()->query()) }}"
                                    class="d-inline mb-0">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        {{ __('messages.clear_preview') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                        <br>

                        <div class="table-responsive table-card mb-1">
                            <table class="table table-nowrap align-middle mb-0">
                                <thead class="text-muted table-light">
                                    <tr class="text-uppercase">
                                        <th style="min-width: 80px;">#</th>
                                        <th style="min-width: 220px;">{{ __('messages.customer_name') }}</th>
                                        <th style="min-width: 160px;">{{ __('messages.phone') }}</th>
                                        <th style="min-width: 160px;">{{ __('messages.contract') }}</th>
                                        <th style="min-width: 180px;">{{ __('messages.branch') }}</th>
                                        <th style="min-width: 500px;">{{ __('messages.sms_message') }}</th>
                                    </tr>
                                </thead>
                                {{-- <tbody>
                                    @foreach ($previewRows as $row)
                                        <tr>
                                            <td>{{ $row['logicalref'] }}</td>
                                            <td>{{ $row['name'] ?: '-' }}</td>
                                            <td>{{ $row['phone'] ?: '-' }}</td>
                                            <td>{{ $row['contract'] ?: '-' }}</td>
                                            <td>{{ $row['branch'] ?: '-' }}</td>
                                            <td style="white-space: pre-wrap; min-width: 500px;">{{ $row['message'] }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody> --}}
                                <tbody>
                                    @foreach (array_slice($previewRows, 0, 5) as $row)
                                        <tr>
                                            <td>{{ $row['logicalref'] }}</td>
                                            <td>{{ $row['name'] ?: '-' }}</td>
                                            <td>{{ $row['phone'] ?: '-' }}</td>
                                            <td>{{ $row['contract'] ?: '-' }}</td>
                                            <td>{{ $row['branch'] ?: '-' }}</td>
                                            <td style="white-space: pre-wrap; min-width: 500px;">
                                                {{ $row['message'] }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if (count($previewRows) > 5)
                            <div class="alert alert-info mt-3">
                                {!! __('messages.total_preview_records_created', ['count' => count($previewRows)]) !!}
                                {!! __('messages.only_first_records_shown', ['limit' => 5]) !!}
                                {!! __('messages.excel_export_contains_all_records') !!}
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if (session('sms_export_skipped_rows'))
                <div class="card mb-4 border-warning">
                    <div class="card-body">
                        <h5 class="mb-3 text-warning">{{ __('messages.skipped_export_numbers') }}</h5>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ __('messages.customer_name') }}</th>
                                        <th>{{ __('messages.phone') }}</th>
                                        <th>{{ __('messages.contract') }}</th>
                                        <th>{{ __('messages.reason') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach (session('sms_export_skipped_rows') as $item)
                                        <tr>
                                            <td>{{ $item['name'] }}</td>
                                            <td>{{ $item['phone'] }}</td>
                                            <td>{{ $item['contract'] }}</td>
                                            <td>
                                                @if ($item['reason'] === 'contains_non_digit')
                                                    {{ __('messages.phone_contains_invalid_characters') }}
                                                @elseif ($item['reason'] === 'contains_letters')
                                                    {{ __('messages.phone_contains_letters') }}
                                                @elseif ($item['reason'] === 'empty')
                                                    {{ __('messages.phone_is_empty') }}
                                                @else
                                                    {{ __('messages.invalid_phone') }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <form id="smsSelectionForm" method="POST" action="{{ route('sms.preview') }}">
                @csrf


                <input type="hidden" name="preview_mode" id="previewMode" value="selected">

                <input type="hidden" name="branch" value="{{ request('branch') }}">
                <input type="hidden" name="name" value="{{ request('name') }}">
                <input type="hidden" name="phone" value="{{ request('phone') }}">
                <input type="hidden" name="passport" value="{{ request('passport') }}">
                <input type="hidden" name="contract" value="{{ request('contract') }}">
                <input type="hidden" name="status_type" value="{{ request('status_type') }}">
                <input type="hidden" name="due_days" value="{{ request('due_days') }}">
                <input type="hidden" name="min_remaining" value="{{ request('min_remaining') }}">
                <input type="hidden" name="max_remaining" value="{{ request('max_remaining') }}">

                <input type="hidden" name="phone_status" value="{{ request('phone_status') }}">
                <input type="hidden" name="payment_date_from" value="{{ request('payment_date_from') }}">
                <input type="hidden" name="payment_date_to" value="{{ request('payment_date_to') }}">


                <div class="card" id="smsList">
                    <div class="card-body pt-4">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllPageBtn">
                                    {{ __('messages.select_all_on_page') }}
                                </button>

                                <button type="button" class="btn btn-sm btn-outline-info" id="previewFilteredBtn">
                                    {{ __('messages.preview_filtered') }}
                                </button>

                                <button type="button" class="btn btn-sm btn-outline-danger" id="clearAllPageBtn">
                                    {{ __('messages.clear_selection') }}
                                </button>
                            </div>

                        </div>
                        <br>
                        <div class="table-responsive table-card mb-1">
                            <table class="table table-nowrap align-middle mb-0" id="smsTable">
                                <thead class="text-muted table-light">
                                    <tr class="text-uppercase">
                                        <th style="width: 50px; min-width: 50px;">
                                            <input type="checkbox" id="checkAllOnPage" class="form-check-input">
                                        </th>
                                        <th style="min-width: 250px;">
                                            {{ __('messages.customer_name') }} <br> {{ __('messages.passport') }}
                                        </th>
                                        <th style="min-width: 160px;">{{ __('messages.phone') }}</th>
                                        <th style="min-width: 180px;">
                                            {{ __('messages.contract') }} <br> {{ __('messages.branch') }}
                                        </th>
                                        <th style="min-width: 130px;">{{ __('messages.credit_date') }} <br>
                                            {{ __('messages.next_payment_date') }}
                                        </th>
                                        <th style="min-width: 150px;">{{ __('messages.payment_status') }} <br>
                                            {{ __('messages.day_info') }} </th>
                                        <th style="min-width: 140px;">{{ __('messages.total_amount') }}</th>
                                        <th style="min-width: 140px;">{{ __('messages.paid_amount') }}</th>
                                        <th style="min-width: 140px;">{{ __('messages.remaining') }}</th>
                                        <th style="min-width: 210px;">{{ __('pages/customers_index.customer_status') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($customers as $customer)
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="selected_customers[]"
                                                    value="{{ $customer->source_id }}"
                                                    class="form-check-input customer-checkbox"
                                                    data-phone="{{ $customer->phone }}">
                                            </td>

                                            <td class="customer_name">
                                                <div class="fw-medium">{{ $customer->name }}</div>
                                                <div class="text-muted small">
                                                    {{ __('pages/customers_index.passport_prefix') }}
                                                    {{ $customer->passport ?? '-' }}
                                                </div>
                                            </td>

                                            <td>{{ $customer->phone ?: '-' }}</td>

                                            <td class="contract">
                                                <div class="fw-medium">{{ $customer->contract }}</div>
                                                <div class="text-muted small">
                                                    {{ $customer->branch ?? '-' }}
                                                </div>
                                            </td>

                                            <td>{{ $customer->date_ ? \Carbon\Carbon::parse($customer->date_)->format('Y-m-d') : '-' }}
                                                <br>
                                                {{ $customer->willpaiddate ? \Carbon\Carbon::parse($customer->willpaiddate)->format('Y-m-d') : '-' }}
                                            </td>

                                            <td>
                                                <span class="badge bg-{{ $customer->status_class }}">
                                                    {{ $customer->status_label }}
                                                </span>
                                                <br> {{ $customer->day_info }}
                                            </td>

                                            {{-- <td>{{ $customer->day_info }}</td> --}}

                                            <td>
                                                {{ number_format((float) ($customer->amount_local ?? ($customer->amount ?? 0)), 2, '.', ' ') }}
                                            </td>

                                            <td>
                                                {{ number_format((float) ($customer->paid_local ?? ($customer->paid ?? 0)), 2, '.', ' ') }}
                                            </td>

                                            <td>
                                                @php
                                                    $remaining = (float) ($customer->local_remaining ?? 0);
                                                @endphp

                                                <span class="badge bg-{{ $remaining > 0 ? 'warning' : 'success' }}">
                                                    {{ number_format($remaining, 2, '.', ' ') }}
                                                </span>
                                            </td>

                                            <td class="customer_status">
                                                <div class="fw-medium">
                                                    {{ $customer->custstatus ?: __('pages/customers_index.unknown') }} /
                                                    @if (!$customer->active)
                                                        <span class="badge bg-danger-subtle text-danger text-uppercase">
                                                            {{ __('pages/customers_index.state_deleted') }}
                                                        </span>
                                                    @elseif ((int) $customer->is_blocked === 1)
                                                        <span class="badge bg-warning-subtle text-warning text-uppercase">
                                                            {{ __('pages/customers_index.state_blocked') }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-success-subtle text-success text-uppercase">
                                                            {{ __('pages/customers_index.state_active') }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="text-muted small">
                                                    {{ $customer->clientref ?? '-' }}
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="14" class="text-center text-muted py-4">
                                                {{ __('messages.no_data_found') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($customers->hasPages())
                            <div class="mt-3">
                                {{ $customers->onEachSide(1)->links('vendor.pagination.custom') }}
                            </div>
                        @endif

                        <hr class="my-4">

                        <div class="row g-3 align-items-end">
                            <div class="col-xxl-3 col-xl-4 col-lg-6">
                                <label class="form-label">{{ __('messages.sms_template_type') }}</label>
                                <select class="form-select" id="smsTemplateType">
                                    <option value="custom">{{ __('messages.custom_message') }}</option>
                                    <option value="due_3_days">{{ __('messages.template_due_3_days') }}</option>
                                    <option value="due_today">{{ __('messages.template_due_today') }}</option>
                                    <option value="overdue">{{ __('messages.template_overdue') }}</option>
                                </select>
                            </div>

                            {{-- <div class="col-xxl-3 col-xl-4 col-lg-6">
                                <label class="form-label">{{ __('messages.schedule_type') }}</label>
                                <select name="schedule_type" id="scheduleType" class="form-select">
                                    <option value="now"
                                        {{ old('schedule_type', $previewScheduleType ?? 'after_2m') === 'now' ? 'selected' : '' }}>
                                        {{ __('messages.send_now') }}
                                    </option>
                                    <option value="after_2m"
                                        {{ old('schedule_type', $previewScheduleType ?? 'after_2m') === 'after_2m' ? 'selected' : '' }}>
                                        {{ __('messages.send_after_2m') }}
                                    </option>
                                    <option value="after_5m"
                                        {{ old('schedule_type', $previewScheduleType ?? 'after_2m') === 'after_5m' ? 'selected' : '' }}>
                                        {{ __('messages.send_after_5m') }}
                                    </option>
                                    <option value="after_10m"
                                        {{ old('schedule_type', $previewScheduleType ?? 'after_2m') === 'after_10m' ? 'selected' : '' }}>
                                        {{ __('messages.send_after_10m') }}
                                    </option>
                                    <option value="custom"
                                        {{ old('schedule_type', $previewScheduleType ?? 'after_2m') === 'custom' ? 'selected' : '' }}>
                                        {{ __('messages.custom_schedule') }}
                                    </option>
                                </select>
                            </div> --}}

                            <div class="col-xxl-3 col-xl-4 col-lg-6" id="customScheduleWrapper" style="display: none;">
                                <label class="form-label">{{ __('messages.scheduled_at') }}</label>
                                <input type="datetime-local" name="scheduled_at" id="scheduledAt" class="form-control"
                                    value="{{ old('scheduled_at', $previewScheduledAt ?? '') ? \Carbon\Carbon::parse(old('scheduled_at', $previewScheduledAt ?? ''))->format('Y-m-d\TH:i') : '' }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label">{{ __('messages.available_variables') }}</label>
                                <div class="small text-muted">
                                    {name} = {{ __('messages.customer_name') }}, {branch} = {{ __('messages.branch') }},
                                    {contract} = {{ __('messages.contract') }}, {remaining} =
                                    {{ __('messages.remaining') }}, {willpaiddate} = {{ __('messages.will_paid_date') }}
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">{{ __('messages.sms_message') }}</label>
                                <textarea name="message" id="smsMessageBox" rows="2" class="form-control"
                                    placeholder="{{ __('messages.sms_message_placeholder') }}">{{ old('message', $previewMessage ?? '') }}</textarea>
                            </div>

                            <div class="col-12 d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary" id="previewSelectionBtn">
                                    {{ __('messages.preview_selected') }}
                                </button>

                                {{-- <button type="button" class="btn btn-success" id="sendSelectionBtn">
                                    {{ __('messages.send_selected') }}
                                </button> --}}
                            </div>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkAllOnPage = document.getElementById('checkAllOnPage');
            const customerCheckboxes = document.querySelectorAll('.customer-checkbox');
            const selectedCountBadge = document.getElementById('selectedCountBadge');
            const selectAllPageBtn = document.getElementById('selectAllPageBtn');
            const clearAllPageBtn = document.getElementById('clearAllPageBtn');
            const smsTemplateType = document.getElementById('smsTemplateType');
            const smsMessageBox = document.getElementById('smsMessageBox');
            const previewSelectionBtn = document.getElementById('previewSelectionBtn');
            // const sendSelectionBtn = document.getElementById('sendSelectionBtn');
            const scheduleType = document.getElementById('scheduleType');
            const customScheduleWrapper = document.getElementById('customScheduleWrapper');

            //hide due_x_days
            const statusTypeSelect = document.querySelector('select[name="status_type"]');
            const dueDaysWrapper = document.getElementById('dueDaysWrapper');
            const dueDaysInput = document.getElementById('dueDaysInput');

            function updateSelectedCount() {
                const checked = document.querySelectorAll('.customer-checkbox:checked').length;
                selectedCountBadge.textContent = checked;
            }

            function updateHeaderCheckboxState() {
                const total = customerCheckboxes.length;
                const checked = document.querySelectorAll('.customer-checkbox:checked').length;

                if (total === 0) {
                    checkAllOnPage.checked = false;
                    checkAllOnPage.indeterminate = false;
                    return;
                }

                if (checked === 0) {
                    checkAllOnPage.checked = false;
                    checkAllOnPage.indeterminate = false;
                } else if (checked === total) {
                    checkAllOnPage.checked = true;
                    checkAllOnPage.indeterminate = false;
                } else {
                    checkAllOnPage.checked = false;
                    checkAllOnPage.indeterminate = true;
                }
            }

            function setAllCheckboxes(state) {
                customerCheckboxes.forEach(function(checkbox) {
                    checkbox.checked = state;
                });

                updateSelectedCount();
                updateHeaderCheckboxState();
            }

            //hide due_x_days
            function toggleDueDays() {
                if (!statusTypeSelect || !dueDaysInput) {
                    return;
                }

                if (statusTypeSelect.value === 'due_in_days') {
                    dueDaysInput.disabled = false;
                } else {
                    dueDaysInput.disabled = true;
                }
            }
            //

            function toggleCustomSchedule() {
                if (!scheduleType || !customScheduleWrapper) {
                    return;
                }

                if (scheduleType.value === 'custom') {
                    customScheduleWrapper.style.display = '';
                } else {
                    customScheduleWrapper.style.display = 'none';
                }
            }


            customerCheckboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    updateSelectedCount();
                    updateHeaderCheckboxState();
                });
            });

            if (checkAllOnPage) {
                checkAllOnPage.addEventListener('change', function() {
                    setAllCheckboxes(this.checked);
                });
            }

            if (selectAllPageBtn) {
                selectAllPageBtn.addEventListener('click', function() {
                    setAllCheckboxes(true);
                });
            }

            if (clearAllPageBtn) {
                clearAllPageBtn.addEventListener('click', function() {
                    setAllCheckboxes(false);
                });
            }

            if (smsTemplateType) {
                smsTemplateType.addEventListener('change', function() {
                    const value = this.value;

                    if (value === 'due_3_days') {
                        smsMessageBox.value =
                            'Hormatly {name}, sizin {branch} magazynynyň {contract} belgeli töleg möhleti {willpaiddate}. Tölegiňize 3 gün galdy. Galyndy: {remaining} TMT.';
                    } else if (value === 'due_today') {
                        smsMessageBox.value =
                            'Hormatly {name}, sizin {contract} belgeli töleg möhleti şu gün ({willpaiddate}). Galyndy: {remaining} TMT.';
                    } else if (value === 'overdue') {
                        smsMessageBox.value =
                            'Hormatly {name}, sizin {contract} belgeli töleg möhleti geçdi. Galyndy bergi: {remaining} TMT. Haýal etmän töleg etmegiňizi haýyş edýäris.';
                    } else {
                        smsMessageBox.value = '';
                    }
                });
            }

            if (scheduleType) {
                scheduleType.addEventListener('change', toggleCustomSchedule);
            }

            if (previewSelectionBtn) {
                previewSelectionBtn.addEventListener('click', function(event) {
                    const checked = document.querySelectorAll('.customer-checkbox:checked').length;

                    if (checked === 0) {
                        event.preventDefault();
                        alert('{{ __('messages.select_at_least_one_customer') }}');
                        return;
                    }

                    if (!smsMessageBox.value.trim()) {
                        event.preventDefault();
                        alert('{{ __('messages.enter_sms_message') }}');
                        return;
                    }

                    document.getElementById('previewMode').value = 'selected';

                    const form = document.getElementById('smsSelectionForm');
                    form.action = '{{ route('sms.preview') }}';
                });
            }

            const previewFilteredBtn = document.getElementById('previewFilteredBtn');

            if (previewFilteredBtn) {
                previewFilteredBtn.addEventListener('click', function() {

                    if (!smsMessageBox.value.trim()) {
                        alert('{{ __('messages.enter_sms_message') }}');
                        return;
                    }

                    document.getElementById('previewMode').value = 'filtered';

                    const form = document.getElementById('smsSelectionForm');
                    form.action = '{{ route('sms.preview') }}';
                    form.submit();
                });
            }

            // if (sendSelectionBtn) {
            //     sendSelectionBtn.addEventListener('click', function() {
            //         const checked = document.querySelectorAll('.customer-checkbox:checked').length;

            //         if (checked === 0) {
            //             alert('{{ __('messages.select_at_least_one_customer') }}');
            //             return;
            //         }

            //         if (!smsMessageBox.value.trim()) {
            //             alert('{{ __('messages.enter_sms_message') }}');
            //             return;
            //         }

            //         const form = document.getElementById('smsSelectionForm');
            //         form.action = '{{ route('sms.send') }}';
            //         form.submit();
            //     });
            // }

            if (statusTypeSelect) {
                statusTypeSelect.addEventListener('change', toggleDueDays);
            }

            toggleDueDays();

            toggleCustomSchedule();
            updateSelectedCount();
            updateHeaderCheckboxState();
        });
    </script>
@endsection
