@props(['status'])

<x-ui.badge :color="match ($status) {
    'packing' => 'warning',
    'ready' => 'info',
    'shipped' => 'success',
    'cancelled' => 'error',
    default => 'gray',
}">
    {{ __('inventory.dispatch.status.'.$status) }}
</x-ui.badge>
