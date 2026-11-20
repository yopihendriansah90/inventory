@props(['status'])

<x-ui.badge :color="match ($status) { 'confirmed' => 'success', 'pending' => 'warning', default => 'error' }">
    {{ __('inventory.status.'.$status) }}
</x-ui.badge>
