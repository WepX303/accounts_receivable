{{-- Gives out the next number on a blank form; the PDF opens in a new tab. --}}
<div class="modal fade" id="blankModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('contracts.blank') }}" target="_blank" class="modal-content"
            onsubmit="setTimeout(function () { window.location.reload(); }, 1500);">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('pages/contracts.blank') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ __('pages/contracts.blank_hint') }}</p>
                <label class="form-label">{{ __('pages/contracts.fields.branch') }}</label>
                <select name="branch" class="form-select" required>
                    @if (count($branches) !== 1)
                        <option value=""></option>
                    @endif
                    @foreach ($branches as $b)
                        <option value="{{ $b }}">{{ $b }}</option>
                    @endforeach
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('pages/contracts.close') }}</button>
                <button type="submit" class="btn btn-primary">
                    <i class="ri-printer-line align-bottom me-1"></i> {{ __('pages/contracts.print') }}
                </button>
            </div>
        </form>
    </div>
</div>
