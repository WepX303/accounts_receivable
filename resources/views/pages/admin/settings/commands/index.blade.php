@extends('layouts.layouts-horizontal')

@section('content')
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="mb-1">{{ __('commands.page_title') }}</h2>
                <p class="text-muted mb-0">
                    {{ __('commands.page_description') }}
                </p>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="row g-4">
            @foreach ($commands as $command)
                <div class="col-12 col-lg-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="card-title mb-0">{{ $command['title'] }}</h5>
                                @if ($command['risk'] === 'low')
                                    <span class="badge bg-success">{{ __('commands.risk.low') }}</span>
                                @elseif ($command['risk'] === 'medium')
                                    <span class="badge bg-warning text-dark">{{ __('commands.risk.medium') }}</span>
                                @else
                                    <span class="badge bg-danger">{{ __('commands.risk.high') }}</span>
                                @endif
                            </div>

                            <p class="text-muted flex-grow-1">
                                {{ $command['description'] }}
                            </p>

                            <form action="{{ $command['route'] }}" method="POST"
                                onsubmit="return confirm('{{ $command['confirm_message'] ?? 'Are you sure you want to run this command?' }}');">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    {{ __('commands.run_command') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
