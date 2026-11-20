<nav data-warehouse-nav aria-label="{{ __('inventory.warehouse.nav') }}"
    class="fixed inset-x-0 bottom-0 z-99 border-t border-gray-200 bg-white px-2 pb-[env(safe-area-inset-bottom)] dark:border-gray-800 dark:bg-gray-900 xl:hidden">
    <div class="mx-auto grid max-w-md grid-cols-5">
        @foreach ([
            ['inventory.home', 'inventory.dashboard.nav', 'svg.bar-chart', ['inventory.home']],
            ['inventory.receiving', 'inventory.dashboard.inbound_nav', 'svg.box', ['inventory.receiving', 'inventory.ticket.receive']],
            ['inventory.receiving.scan', 'inventory.warehouse.scan', 'svg.qr-scan', ['inventory.receiving.scan', 'inventory.dispatch.scan']],
            ['inventory.dispatch.index', 'inventory.dispatch.nav', 'svg.box', ['inventory.dispatch.index', 'inventory.dispatch.show']],
            ['inventory.stock', 'inventory.warehouse.stock', 'svg.table', ['inventory.stock']],
        ] as [$route, $label, $icon, $activeRoutes])
            @php($active = request()->routeIs(...$activeRoutes))
            <a href="{{ route($route) }}" @if($active) aria-current="page" @endif
                @class(['flex min-h-16 flex-col items-center justify-center gap-1 rounded-lg px-1 py-1 text-center text-xs leading-tight font-medium focus-visible:outline-brand-500',
                    'text-brand-700 dark:text-brand-300' => $active,
                    'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' => ! $active])>
                <span @class(['flex h-7 w-10 items-center justify-center rounded-full sm:w-14', 'bg-brand-100 dark:bg-brand-500/20' => $active])>
                    <x-dynamic-component :component="$icon" class="size-5" aria-hidden="true" />
                </span>
                {{ __($label) }}
            </a>
        @endforeach
    </div>
</nav>
