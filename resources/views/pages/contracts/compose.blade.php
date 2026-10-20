@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">{{ __('pages/contracts.new') }}</h5>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('contracts.create', $q !== '' ? ['q' => $q] : []) }}" class="btn btn-soft-secondary">
                                <i class="ri-arrow-left-line align-bottom me-1"></i> {{ __('pages/contracts.back') }}
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if ($credit)
                        <div class="alert alert-info mb-0">
                            {{ __('pages/contracts.filled_from') }}: <strong>{{ $credit->contract }}</strong>
                            — {{ $credit->name }} ({{ $credit->branch }})
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">{{ __('pages/contracts.manual_hint') }}</div>
                    @endif
                </div>
            </div>

            @include('pages.contracts._document', [
                'number' => null,
                'action' => route('contracts.store'),
                'method' => 'POST',
                'submitLabel' => __('pages/contracts.save_new'),
            ])
        </div>
    </div>
@endsection
