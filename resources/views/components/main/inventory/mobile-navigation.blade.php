@props(['warehouse', 'draft' => false])

@php
    $home = [route('inventory.home'), 'inventory.dashboard.nav', 'svg.bar-chart', request()->routeIs('inventory.home')];
    $tabs = $warehouse ? [
        $home,
        [route('inventory.receiving'), 'inventory.dashboard.inbound_nav', 'svg.box', request()->routeIs('inventory.receiving', 'inventory.ticket.receive')],
        [route('inventory.receiving.scan'), 'inventory.warehouse.scan', 'svg.qr-scan', request()->routeIs('inventory.receiving.scan', 'inventory.dispatch.scan')],
        [route('inventory.dispatch.index'), 'inventory.dispatch.nav', 'svg.box', request()->routeIs('inventory.dispatch.index', 'inventory.dispatch.show')],
        [route('inventory.stock'), 'inventory.warehouse.stock', 'svg.table', request()->routeIs('inventory.stock')],
    ] : [
        $home,
        [route('inventory.qc'), 'inventory.qc.nav_tickets', 'svg.table', ! $draft && request()->routeIs('inventory.qc', 'inventory.ticket.show')],
        [route('inventory.qc', ['new' => 1]).'#inspection', 'inventory.qc.nav_create', 'svg.plus', $draft],
        [route('inventory.qc.scan'), 'inventory.warehouse.scan', 'svg.qr-scan', request()->routeIs('inventory.qc.scan')],
    ];
@endphp

<nav @if ($warehouse) data-warehouse-nav @else data-qc-nav @endif aria-label="{{ __($warehouse ? 'inventory.warehouse.nav' : 'inventory.qc.nav') }}"
    class="fixed inset-x-0 bottom-0 z-99 border-t border-gray-200 bg-white px-2 pb-[env(safe-area-inset-bottom)] dark:border-gray-800 dark:bg-gray-900 xl:hidden">
    <div @class(['mx-auto grid max-w-md', 'grid-cols-5' => $warehouse, 'grid-cols-4' => ! $warehouse])>
        @foreach ($tabs as [$url, $label, $icon, $active])
            <a href="{{ $url }}" @if($active) aria-current="page" @endif
                @class(['flex min-w-0 min-h-16 flex-col items-center gap-1 rounded-lg px-0.5 pt-2 pb-1 text-center text-xs leading-tight font-medium focus-visible:outline-brand-500',
                    'text-brand-700 dark:text-brand-300' => $active,
                    'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' => ! $active])>
                <span @class(['flex h-7 w-10 shrink-0 items-center justify-center rounded-full sm:w-14', 'bg-brand-100 dark:bg-brand-500/20' => $active])>
                    <x-dynamic-component :component="$icon" class="size-5" aria-hidden="true" />
                </span>
                {{ __($label) }}
            </a>
        @endforeach
    </div>
</nav>
