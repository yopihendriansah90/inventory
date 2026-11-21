<header @class(['sticky top-0 z-50 flex items-center justify-between gap-2 border-b border-gray-200 bg-white px-3 py-3 dark:border-gray-800 dark:bg-gray-900 sm:gap-3 sm:px-4 md:px-6',
    'pt-[calc(0.75rem+env(safe-area-inset-top))] xl:pt-3' => $mobileNavigation])>
    <div class="flex min-w-0 items-center gap-2 sm:gap-3">
        <x-ui.button variant="ghost" size="icon" class="hidden xl:inline-flex" aria-label="{{ __('header.toggleSidebar') }}" @click="$store.sidebar.toggleExpanded()">
            <x-svg.menu-two class="size-5 rtl:-scale-x-100" />
        </x-ui.button>
        @unless ($mobileNavigation)
            <x-ui.button variant="ghost" size="icon" class="xl:hidden" aria-label="{{ __('header.toggleSidebar') }}" @click="$store.sidebar.toggleMobileOpen()">
                <x-svg.menu-two class="size-5 rtl:-scale-x-100" />
            </x-ui.button>
        @endunless
        <a href="{{ route('inventory.home') }}" class="truncate font-semibold text-gray-900 dark:text-white">{{ __('inventory.title') }}</a>
    </div>
    <div class="flex shrink-0 items-center gap-2 sm:gap-3">
        @sectionMissing('inventory-draft')
            <x-main.inventory.language compact variant="ghost" />
        @endif
        <span class="hidden text-sm text-gray-600 dark:text-gray-300 md:inline">{{ auth()->user()->name }}</span>
        <x-ui.button variant="ghost" size="icon" aria-label="{{ __('inventory.theme') }}" @click="$store.theme.toggle()">
            <x-svg.sun x-show="$store.theme.resolvedTheme === 'dark'" />
            <x-svg.moon x-show="$store.theme.resolvedTheme !== 'dark'" />
        </x-ui.button>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-ui.button type="submit" variant="ghost" size="sm" class="size-10 px-0 sm:h-9 sm:w-auto sm:px-4"
                :aria-label="__('inventory.logout')">
                <x-svg.log-out class="size-5 sm:hidden" aria-hidden="true" />
                <span class="hidden sm:inline">{{ __('inventory.logout') }}</span>
            </x-ui.button>
        </form>
    </div>
</header>
