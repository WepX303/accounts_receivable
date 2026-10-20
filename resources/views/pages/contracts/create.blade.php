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
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- The buttons take the room their labels need; on a narrow screen they move below. --}}
                    <form method="GET" action="{{ route('contracts.create') }}" class="d-flex flex-wrap gap-2">
                        <div class="search-box flex-grow-1" style="min-width: 260px;">
                            <input type="text" name="q" class="form-control" maxlength="100" autofocus
                                placeholder="{{ __('pages/contracts.search_customer') }}" value="{{ $q }}">
                            <i class="ri-search-line search-icon"></i>
                        </div>
                        <button type="submit" class="btn btn-primary text-nowrap">
                            <i class="ri-search-line align-bottom me-1"></i> {{ __('pages/contracts.search') }}
                        </button>
                        <a href="{{ route('contracts.create', ['manual' => 1]) }}" class="btn btn-soft-warning text-nowrap">
                            <i class="ri-edit-line align-bottom me-1"></i> {{ __('pages/contracts.manual') }}
                        </a>
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
                                        <tr>
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
                                                <a href="{{ route('contracts.create', ['q' => $q, 'credit' => $c->source_id]) }}"
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

        </div>
    </div>
@endsection
