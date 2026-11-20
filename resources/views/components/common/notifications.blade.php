@php
    $firstError = collect($errors->getBags())->first(fn ($bag) => $bag->any())?->first();
    $message = $firstError ?: session('status');
@endphp

<div hidden data-app-notification data-close-label="{{ __('Close') }}"
    @if ($message)
        data-icon="{{ $firstError ? 'error' : 'success' }}" data-message="{{ $message }}"
    @endif
></div>
