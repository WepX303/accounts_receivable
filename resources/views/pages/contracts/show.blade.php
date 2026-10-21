@extends('layouts.layouts-horizontal')

@section('content')
    @php
        $fieldLabel = function (string $key) {
            return match ($key) {
                'goods' => __('pages/contracts.section_goods'),
                'history' => __('pages/contracts.section_history'),
                default => \Illuminate\Support\Facades\Lang::has('pages/contracts.fields.' . $key)
                    ? __('pages/contracts.fields.' . $key)
                    : $key,
            };
        };
        $shown = fn ($v) => is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string) $v;
    @endphp

    <div class="row">
        <div class="col-lg-12">

            {{-- Header and actions --}}
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-1">
                                {{ __('pages/contracts.fields.number') }} {{ $contract->number }}
                                @include('pages.contracts._status', ['status' => $contract->status])
                            </h5>
                            <div class="text-muted">{{ $contract->customer_name ?: '—' }} · {{ $contract->branch }}</div>
                        </div>
                        <div class="col-sm-auto d-flex flex-wrap gap-2">
                            <a href="{{ route('contracts.index') }}" class="btn btn-soft-secondary">
                                <i class="ri-arrow-left-line align-bottom me-1"></i> {{ __('pages/contracts.back') }}
                            </a>
                            {{-- The first print locks a contract: ask in a modal, then show it locked. --}}
                            @if ($contract->isDraft())
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#printModal">
                                    <i class="ri-printer-line align-bottom me-1"></i> {{ __('pages/contracts.print') }}
                                </button>
                            @else
                                <a href="{{ route('contracts.print', $contract) }}" target="_blank" class="btn btn-primary">
                                    <i class="ri-printer-line align-bottom me-1"></i> {{ __('pages/contracts.print') }}
                                </a>
                            @endif
                            @if ($canCancel)
                                <button type="button" class="btn btn-soft-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                    <i class="ri-close-circle-line align-bottom me-1"></i> {{ __('pages/contracts.cancel') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="text-muted small">{{ __('pages/contracts.fields.contract_date') }}</div>
                            <div class="fw-medium">{{ $contract->contract_date?->format('d.m.Y') }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">{{ __('pages/contracts.fields.created_by') }}</div>
                            <div class="fw-medium">{{ $contract->created_by_name ?? '-' }}</div>
                            <div class="text-muted small">{{ $contract->created_at?->format('d.m.Y H:i') }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">{{ __('pages/contracts.fields.printed_by') }}</div>
                            <div class="fw-medium">{{ $contract->printed_by_name ?? '-' }}</div>
                            <div class="text-muted small">{{ $contract->printed_at?->format('d.m.Y H:i') }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">{{ __('pages/contracts.fields.print_count') }}</div>
                            <div class="fw-medium">{{ $contract->print_count }}</div>
                            <div class="text-muted small">{{ $contract->last_printed_at?->format('d.m.Y H:i') }}</div>
                        </div>
                        @if ($contract->isCancelled())
                            <div class="col-12">
                                <div class="alert alert-danger mb-0">
                                    <strong>{{ __('pages/contracts.fields.cancelled_by') }}:</strong> {{ $contract->cancelled_by_name }}
                                    ({{ $contract->cancelled_at?->format('d.m.Y H:i') }})<br>
                                    <strong>{{ __('pages/contracts.fields.cancel_reason') }}:</strong> {{ $contract->cancel_reason }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- A draft is changed here; anything else is shown as it was signed --}}
            @if ($canEdit)
                @include('pages.contracts._document', [
                    'number' => $contract->number,
                    'form' => $contract->only([
                        'credit_source_id', 'branch', 'contract_date', 'customer_name', 'passport_series', 'passport_number',
                        'address', 'phone', 'months', 'monthly_amount', 'installment_part', 'goods', 'history',
                    ]),
                    'action' => route('contracts.update', $contract),
                    'method' => 'PUT',
                    'submitLabel' => __('pages/contracts.save_changes'),
                ])
            @else
                @if (! $contract->isDraft())
                    <div class="alert alert-secondary">{{ __('pages/contracts.locked') }}</div>
                @endif
                @include('pages.contracts._document', [
                    'editable' => false,
                    'number' => $contract->number,
                    'form' => $contract->only([
                        'branch', 'contract_date', 'customer_name', 'passport_series', 'passport_number',
                        'address', 'phone', 'months', 'monthly_amount', 'installment_part', 'goods', 'history',
                    ]),
                ])
            @endif

            {{-- Who did what --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('pages/contracts.changes') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('pages/contracts.fields.when') }}</th>
                                    <th>{{ __('pages/contracts.fields.user') }}</th>
                                    <th>{{ __('pages/contracts.fields.action') }}</th>
                                    <th>{{ __('pages/contracts.fields.old') }} → {{ __('pages/contracts.fields.new') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($changes as $log)
                                    <tr>
                                        <td class="text-nowrap">{{ $log->created_at?->format('d.m.Y H:i:s') }}</td>
                                        <td>{{ $log->user_name ?? '-' }}</td>
                                        <td>
                                            {{ \Illuminate\Support\Facades\Lang::has('pages/contracts.actions.' . $log->action) ? __('pages/contracts.actions.' . $log->action) : $log->action }}
                                            @if ($log->action === 'contract_cancelled' && ! empty($log->extra['reason']))
                                                <div class="text-muted small">{{ $log->extra['reason'] }}</div>
                                            @endif
                                        </td>
                                        <td class="small">
                                            @if ($log->action === 'contract_updated')
                                                @foreach ((array) $log->new_values as $key => $new)
                                                    <div>
                                                        <span class="text-muted">{{ $fieldLabel($key) }}:</span>
                                                        <span class="text-danger text-decoration-line-through">{{ \Illuminate\Support\Str::limit($shown($log->old_values[$key] ?? ''), 120) }}</span>
                                                        → <span class="text-success">{{ \Illuminate\Support\Str::limit($shown($new), 120) }}</span>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">{{ __('pages/contracts.no_changes') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($contract->isDraft())
        <div class="modal fade" id="printModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('pages/contracts.print') }} — {{ $contract->number }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning d-flex gap-2 mb-3">
                            <i class="ri-lock-line fs-18"></i>
                            <div>{{ __('pages/contracts.print_confirm') }}</div>
                        </div>
                        <p class="text-muted mb-0">{{ __('pages/contracts.print_copies_hint') }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('pages/contracts.close') }}</button>
                        <a href="{{ route('contracts.print', $contract) }}" target="_blank" class="btn btn-primary"
                            onclick="bootstrap.Modal.getInstance(document.getElementById('printModal'))?.hide(); setTimeout(function () { window.location.reload(); }, 1500);">
                            <i class="ri-printer-line align-bottom me-1"></i> {{ __('pages/contracts.print') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($canCancel)
        <div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('contracts.cancel', $contract) }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('pages/contracts.cancel') }} — {{ $contract->number }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">{{ __('pages/contracts.cancel_hint') }}</p>
                        <label class="form-label">{{ __('pages/contracts.cancel_reason') }}</label>
                        <textarea name="cancel_reason" class="form-control" rows="3" maxlength="1000" required>{{ old('cancel_reason') }}</textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('pages/contracts.close') }}</button>
                        <button type="submit" class="btn btn-danger">{{ __('pages/contracts.cancel') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
