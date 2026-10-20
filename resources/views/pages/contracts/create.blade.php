@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row">
        <div class="col-lg-12">

            {{-- Find the customer --}}
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">{{ __('pages/contracts.new') }}</h5>
                        </div>
                        <div class="col-sm-auto d-flex flex-wrap gap-2">
                            <a href="{{ route('contracts.index') }}" class="btn btn-soft-secondary">
                                <i class="ri-arrow-left-line align-bottom me-1"></i> {{ __('pages/contracts.back') }}
                            </a>
                            <button type="button" class="btn btn-soft-secondary" data-bs-toggle="modal" data-bs-target="#blankModal">
                                <i class="ri-printer-line align-bottom me-1"></i> {{ __('pages/contracts.blank') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" action="{{ route('contracts.create') }}" class="row g-2">
                        <div class="col-md-8">
                            <div class="search-box">
                                <input type="text" name="q" class="form-control" maxlength="100" autofocus
                                    placeholder="{{ __('pages/contracts.search_customer') }}" value="{{ $q }}">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">{{ __('pages/contracts.search') }}</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('contracts.create', ['manual' => 1]) }}" class="btn btn-soft-warning w-100">
                                {{ __('pages/contracts.manual') }}
                            </a>
                        </div>
                    </form>

                    @if ($credits !== null)
                        <div class="table-responsive table-card mt-3">
                            <table class="table align-middle table-nowrap mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('pages/customers_info.name') }}</th>
                                        <th>{{ __('pages/customers_info.phone') }}</th>
                                        <th>{{ __('pages/customers_info.branch') }}</th>
                                        <th>{{ __('pages/customers_info.contract') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($credits as $c)
                                        <tr @class(['table-active' => $credit && (int) $credit->source_id === (int) $c->source_id])>
                                            <td>
                                                <div class="fw-medium">{{ $c->name }}</div>
                                                <div class="text-muted small">{{ $c->passport ?? '-' }}</div>
                                            </td>
                                            <td>{{ $c->phone }}</td>
                                            <td>{{ $c->branch }}</td>
                                            <td>
                                                <div>{{ $c->contract }}</div>
                                                <div class="text-muted small">{{ $c->date_?->format('d.m.Y') ?? '-' }}</div>
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('contracts.create', ['q' => $q, 'credit' => $c->source_id, 'page' => request('page')]) }}"
                                                    class="btn btn-sm btn-primary">{{ __('pages/contracts.select') }}</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">{{ __('pages/contracts.no_results') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $credits->links('vendor.pagination.custom') }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- The form --}}
            @if ($form !== null)
                @if ($credit)
                    <div class="alert alert-info">
                        {{ __('pages/contracts.filled_from') }}: <strong>{{ $credit->contract }}</strong>
                        — {{ $credit->name }} ({{ $credit->branch }})
                    </div>
                @else
                    <div class="alert alert-warning">{{ __('pages/contracts.manual_hint') }}</div>
                @endif

                @include('pages.contracts._form', [
                    'action' => route('contracts.store'),
                    'method' => 'POST',
                    'submitLabel' => __('pages/contracts.save_draft'),
                ])
            @endif
        </div>
    </div>

    @include('pages.contracts._blank')
@endsection
