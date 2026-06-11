@extends('layouts.layouts-horizontal')

@section('content')
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-0">Total Logs</p>
                    <h4 class="mt-3 mb-0">{{ $stats['total'] }}</h4>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-0">Activity Logs</p>
                    <h4 class="mt-3 mb-0">{{ $stats['activity'] }}</h4>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-0">Login History</p>
                    <h4 class="mt-3 mb-0">{{ $stats['login'] }}</h4>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-0">Security Alerts</p>
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
                        <label class="form-label">Search</label>
                        <input type="text" name="q" class="form-control" value="{{ request('q') }}"
                            placeholder="action, category, user, ip, route, browser...">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="all" {{ request('type', 'all') === 'all' ? 'selected' : '' }}>All</option>
                            <option value="activity" {{ request('type') === 'activity' ? 'selected' : '' }}>Activity
                            </option>
                            <option value="login" {{ request('type') === 'login' ? 'selected' : '' }}>Login</option>
                            <option value="alert" {{ request('type') === 'alert' ? 'selected' : '' }}>Alert</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label d-block">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary w-100" type="submit">
                                <i class="ri-search-line align-bottom me-1"></i> Filter
                            </button>
                            <a href="{{ route('logs') }}" class="btn btn-light w-100">
                                Reset
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
                            <th>Date</th>
                            <th>Type</th>
                            <th>Title</th>
                            <th>User</th>
                            <th>Status</th>
                            <th>Level</th>
                            <th>IP</th>
                            <th>Message</th>
                            <th class="text-end">Detail</th>
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
                                        {{ ucfirst($log['type']) }}
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
                                <td>{{ $log['ip_address'] ?: '-' }}</td>
                                <td class="text-truncate" style="max-width: 280px;">
                                    {{ $log['message'] ?: '-' }}
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-soft-primary" data-bs-toggle="modal"
                                        data-bs-target="#{{ $modalId }}">
                                        View
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    No logs found.
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
                            Log Detail #{{ $log['id'] }} - {{ ucfirst($log['type']) }}
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
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection
