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

                            <p class="text-muted mb-2">
                                {{ $command['description'] }}
                            </p>

                            @if (!empty($command['details']))
                                <p class="small text-muted mb-3">
                                    {{ $command['details'] }}
                                </p>
                            @endif

                            @if (!empty($command['artisan_command']))
                                <div class="mb-3">
                                    <span class="badge bg-light text-dark border">
                                        {{ $command['artisan_command'] }}
                                    </span>
                                </div>
                            @endif

                            <div class="mt-auto">
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
                </div>
            @endforeach
        </div>
    </div>
@endsection
