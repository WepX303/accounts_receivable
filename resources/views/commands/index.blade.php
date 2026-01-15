@extends('layouts.layouts-horizontal')



@section('content')
<div class="container-fluid py-4">
    <h1 class="mb-4 text-primary fw-bold">Artisan Komutları</h1>

    {{-- Komut sonucu alert --}}
    @if(session('output'))
        <div class="alert alert-{{ session('status') == 'success' ? 'success' : 'danger' }} alert-dismissible fade show" role="alert">
            <strong>{{ session('command') }}:</strong>
            <pre class="mb-0" style="white-space: pre-wrap;">{{ session('output') }}</pre>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3">
        @foreach($commands as $key => $label)
            <div class="col-md-4">
                <form method="POST" action="{{ route('commands.run') }}" class="h-100">
                    @csrf
                    <input type="hidden" name="command" value="{{ $key }}">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon text-primary fs-2 me-3">
                                    <i class="mdi mdi-play-circle-outline"></i>
                                </div>
                                <div>
                                    <h5 class="card-title mb-0 fw-bold">{{ $label }}</h5>
                                    <small class="text-muted">{{ $key }}</small>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="mdi mdi-rocket me-2"></i> Çalıştır
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        @endforeach
    </div>
</div>
@endsection
    @section('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    @endsection
