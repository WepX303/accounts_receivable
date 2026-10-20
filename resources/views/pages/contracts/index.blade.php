@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">{{ __('pages/contracts.title') }}</h5>
                        </div>
                        <div class="col-sm-auto d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-soft-secondary" data-bs-toggle="modal" data-bs-target="#blankModal">
                                <i class="ri-printer-line align-bottom me-1"></i> {{ __('pages/contracts.blank') }}
                            </button>
                            <a href="{{ route('contracts.create') }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> {{ __('pages/contracts.create') }}
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body border-bottom-dashed border-bottom">
                    <form method="GET" action="{{ route('contracts.index') }}" class="row g-2">
                        <div class="col-md-6">
                            <div class="search-box">
                                <input type="text" name="q" class="form-control" maxlength="100"
                                    placeholder="{{ __('pages/contracts.search_list') }}" value="{{ $q }}">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="">{{ __('pages/contracts.all_statuses') }}</option>
                                @foreach (['issued', 'draft', 'approved', 'cancelled'] as $s)
                                    <option value="{{ $s }}" @selected($status === $s)>{{ __('pages/contracts.statuses.' . $s) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ri-equalizer-fill align-bottom me-1"></i> {{ __('pages/contracts.filter') }}
                            </button>
                        </div>
                    </form>
                </div>

                <div class="card-body">
                    <div class="table-responsive table-card mb-1">
                        <table class="table align-middle table-nowrap mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>{{ __('pages/contracts.fields.number') }}</th>
                                    <th>{{ __('pages/contracts.fields.contract_date') }}</th>
                                    <th>{{ __('pages/contracts.fields.customer_name') }}</th>
                                    <th>{{ __('pages/contracts.fields.branch') }}</th>
                                    <th>{{ __('pages/contracts.fields.status') }}</th>
                                    <th>{{ __('pages/contracts.fields.created_by') }}</th>
                                    <th class="text-end">{{ __('pages/contracts.fields.print_count') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($contracts as $contract)
                                    <tr>
                                        <td>
                                            <a href="{{ route('contracts.show', $contract) }}" class="fw-medium link-primary">{{ $contract->number }}</a>
                                        </td>
                                        <td>{{ $contract->contract_date?->format('d.m.Y') }}</td>
                                        <td>
                                            <div class="fw-medium">{{ $contract->customer_name ?: '—' }}</div>
                                            <div class="text-muted small">{{ trim($contract->passport_series . ' ' . $contract->passport_number) ?: '-' }}</div>
                                        </td>
                                        <td>{{ $contract->branch }}</td>
                                        <td>@include('pages.contracts._status', ['status' => $contract->status])</td>
                                        <td>
                                            <div>{{ $contract->created_by_name }}</div>
                                            <div class="text-muted small">{{ $contract->created_at?->format('d.m.Y H:i') }}</div>
                                        </td>
                                        <td class="text-end">{{ $contract->print_count }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">{{ __('pages/contracts.empty_list') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $contracts->links('vendor.pagination.custom') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('pages.contracts._blank')
@endsection
