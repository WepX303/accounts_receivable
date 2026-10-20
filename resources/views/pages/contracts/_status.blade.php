@php
    $badge = [
        'draft' => 'bg-warning-subtle text-warning',
        'printed' => 'bg-success-subtle text-success',
        'cancelled' => 'bg-danger-subtle text-danger',
    ][$status] ?? 'bg-light text-muted';
@endphp
<span class="badge {{ $badge }}">{{ __('pages/contracts.statuses.' . $status) }}</span>
