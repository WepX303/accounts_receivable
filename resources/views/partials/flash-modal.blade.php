{{-- resources/views/partials/flash-modal.blade.php --}}

@php
    $hasErrors = $errors->any();
    $messages = [];

    if (session('success')) $messages[] = ['type' => 'success', 'text' => session('success')];
    if (session('warning')) $messages[] = ['type' => 'warning', 'text' => session('warning')];
    if (session('info'))    $messages[] = ['type' => 'info',    'text' => session('info')];
    if (session('error'))   $messages[] = ['type' => 'error',   'text' => session('error')];

    // validation errors (hepsini listele)
    if ($hasErrors) {
        foreach ($errors->all() as $e) {
            $messages[] = ['type' => 'validation', 'text' => $e];
        }
    }

    // modal rengi
    $modalClass = 'primary';
    if ($hasErrors) $modalClass = 'danger';
    elseif (session('warning')) $modalClass = 'warning';
    elseif (session('success')) $modalClass = 'success';
    elseif (session('info')) $modalClass = 'info';

    $showModal = count($messages) > 0;
@endphp

@if($showModal)
<div class="modal fade" id="flashModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-{{ $modalClass }} text-white">
        <h5 class="modal-title">
            @if($hasErrors)
                {{ __('common.validation_title') }}
            @elseif(session('warning'))
                {{ __('common.warning_title') }}
            @elseif(session('success'))
                {{ __('common.success_title') }}
            @elseif(session('info'))
                {{ __('common.info_title') }}
            @else
                {{ __('common.message_title') }}
            @endif
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <ul class="mb-0 ps-3">
            @foreach($messages as $m)
                <li class="mb-1">{{ $m['text'] }}</li>
            @endforeach
        </ul>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-{{ $modalClass }}" data-bs-dismiss="modal">
            {{ __('common.close') }}
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('flashModal');
    if (!modalEl) return;
    var modal = new bootstrap.Modal(modalEl);
    modal.show();
  });
</script>
@endif