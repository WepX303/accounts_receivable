@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-0">{{ __('pages/logs.total') }}</p>
                    <h4 class="mt-3 mb-0">{{ $stats['total'] }}</h4>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-0">{{ __('pages/logs.activity') }}</p>
                    <h4 class="mt-3 mb-0">{{ $stats['activity'] }}</h4>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-0">{{ __('pages/logs.login') }}</p>
                    <h4 class="mt-3 mb-0">{{ $stats['login'] }}</h4>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-0">{{ __('pages/logs.alert') }}</p>
                    <h4 class="mt-3 mb-0">{{ $stats['alert'] }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('logs') }}">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('pages/logs.search') }}</label>
                        <input type="text" name="q" class="form-control" value="{{ request('q') }}"
                            placeholder="{{ __('pages/logs.search_placeholder') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">{{ __('pages/logs.type') }}</label>
                        <select name="type" class="form-select">
                            <option value="all" {{ request('type', 'all') === 'all' ? 'selected' : '' }}>{{ __('pages/logs.all') }}</option>
                            <option value="activity" {{ request('type') === 'activity' ? 'selected' : '' }}>{{ __('pages/logs.type_activity') }}</option>
                            <option value="login" {{ request('type') === 'login' ? 'selected' : '' }}>{{ __('pages/logs.type_login') }}</option>
                            <option value="alert" {{ request('type') === 'alert' ? 'selected' : '' }}>{{ __('pages/logs.type_alert') }}</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label d-block">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary w-100" type="submit">
                                <i class="ri-search-line align-bottom me-1"></i> {{ __('pages/logs.filter') }}
                            </button>
                            <a href="{{ route('logs') }}" class="btn btn-light w-100">
                                {{ __('pages/logs.reset') }}
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body">
            <div class="table-responsive table-card">
                <table class="table table-nowrap align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('pages/logs.date') }}</th>
                            <th>{{ __('pages/logs.type') }}</th>
                            <th>{{ __('pages/logs.entry') }}</th>
                            <th>{{ __('pages/logs.user') }}</th>
                            <th>{{ __('pages/logs.status') }}</th>
                            <th>{{ __('pages/logs.level') }}</th>
                            <th>{{ __('pages/logs.client') }}</th>
                            <th>{{ __('pages/logs.ip') }}</th>
                            <th>{{ __('pages/logs.message') }}</th>
                            <th class="text-end">{{ __('pages/logs.detail') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $index => $log)
                            @php
                                $typeClass = match ($log['type']) {
                                    'activity' => 'primary',
                                    'login' => 'success',
                                    'alert' => 'danger',
                                    default => 'secondary',
                                };

                                $statusClass = match (strtolower((string) $log['status_text'])) {
                                    'success', 'resolved' => 'success',
                                    'failed', 'open' => 'danger',
                                    'logout' => 'warning',
                                    default => 'secondary',
                                };

                                $levelClass = match (strtolower((string) $log['level_text'])) {
                                    'info', 'low' => 'info',
                                    'warning', 'medium' => 'warning',
                                    'high' => 'danger',
                                    'critical' => 'dark',
                                    default => 'secondary',
                                };

                                $modalId = 'logDetailModal_' . $index . '_' . $log['type'] . '_' . $log['id'];
                            @endphp

                            <tr>
                                <td>{{ optional($log['created_at'])->format('Y-m-d H:i:s') }}</td>
                                <td>
                                    <span class="badge bg-{{ $typeClass }}">
                                        {{ __('pages/logs.type_' . $log['type']) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $log['main_title'] ?: '-' }}</div>
                                    <small class="text-muted">{{ $log['sub_title'] ?: '-' }}</small>
                                </td>
                                <td>{{ $log['user_text'] ?: '-' }}</td>
                                <td>
                                    <span class="badge bg-{{ $statusClass }}">
                                        {{ $log['status_text'] ?: '-' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $levelClass }}">
                                        {{ $log['level_text'] ?: '-' }}
                                    </span>
                                </td>
                                <td>
                                    {{-- Where the entry came from: the panel, the app or a scheduled command. --}}
                                    @php
                                        $client = in_array($log['client'] ?? null, ['web', 'mobile', 'system'], true) ? $log['client'] : 'unknown';
                                        $clientClass = ['web' => 'primary', 'mobile' => 'success', 'system' => 'secondary', 'unknown' => 'light text-muted'][$client];
                                    @endphp
                                    <span class="badge bg-{{ $clientClass }}">{{ __('pages/logs.client_' . $client) }}</span>
                                </td>
                                <td>{{ $log['ip_address'] ?: '-' }}</td>
                                <td class="text-truncate" style="max-width: 280px;">
                                    {{ $log['message'] ?: '-' }}
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-soft-primary" data-bs-toggle="modal"
                                        data-bs-target="#{{ $modalId }}">
                                        {{ __('pages/logs.view') }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    {{ __('pages/logs.empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($logs->hasPages())
                <div class="row mt-4">
                    <div class="col-12 d-flex justify-content-end">
                        {{ $logs->appends(request()->query())->onEachSide(1)->links('vendor.pagination.custom') }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Modals table dışına alındı --}}
    @foreach ($logs as $index => $log)
        @php
            $modalId = 'logDetailModal_' . $index . '_' . $log['type'] . '_' . $log['id'];
        @endphp

        <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ __('pages/logs.detail_title') }} #{{ $log['id'] }} - {{ __('pages/logs.type_' . $log['type']) }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            @foreach ($log['raw'] as $key => $value)
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold text-muted mb-1">
                                        {{ ucwords(str_replace('_', ' ', $key)) }}
                                    </label>

                                    @if (is_array($value))
                                        <pre class="bg-light border rounded p-3 small mb-0" style="white-space: pre-wrap;">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    @elseif(is_bool($value))
                                        <div>
                                            <span class="badge bg-{{ $value ? 'success' : 'danger' }}">
                                                {{ $value ? 'true' : 'false' }}
                                            </span>
                                        </div>
                                    @elseif($value === null || $value === '')
                                        <div class="text-muted">-</div>
                                    @else
                                        <div class="border rounded p-2 bg-light">
                                            {{ $value }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            {{ __('pages/logs.close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection
