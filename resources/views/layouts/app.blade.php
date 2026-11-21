@php
    $inventoryPage = request()->routeIs('inventory.*');
    $warehouseNavigation = $inventoryPage && auth()->user()?->can('receive-stock');
    $mobileNavigation = $warehouseNavigation || ($inventoryPage && auth()->user()?->can('record-qc'));
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="ltr" class="h-full bg-gray-50 dark:bg-gray-900">

<head>
    <script>
        (function () {
            if (localStorage.getItem('dir') === 'rtl') document.documentElement.setAttribute('dir', 'rtl');
        })();
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1{{ $mobileNavigation ? ', viewport-fit=cover' : '' }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('layouts.head-meta')

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Theme Store -->
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    <!-- Theme Store -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('theme', {
                init() {
                    const savedTheme = localStorage.getItem('theme');
                    this.theme = savedTheme === 'dark' ? 'dark' : 'light';
                    this.updateTheme();
                },
                theme: 'light',
                resolvedTheme: 'light',
                set(value) {
                    value = value === 'dark' ? 'dark' : 'light';
                    this.theme = value;
                    localStorage.setItem('theme', value);
                    this.updateTheme();
                    window.dispatchEvent(new CustomEvent('theme-changed', { detail: value }));
                },
                toggle() {
                    this.set(this.resolvedTheme === 'dark' ? 'light' : 'dark');
                },
                updateTheme() {
                    const html = document.documentElement;
                    const isDark = this.theme === 'dark';
                    if (isDark) {
                        html.classList.add('dark');
                    } else {
                        html.classList.remove('dark');
                    }

                    this.resolvedTheme = isDark ? 'dark' : 'light';
                    html.setAttribute('data-color-scheme', this.resolvedTheme);
                    html.dataset['theme'] = this.resolvedTheme;
                    html.style.colorScheme = this.resolvedTheme;
                    if (document.body) {
                        document.body.dataset['theme'] = this.resolvedTheme;
                        document.body.style.colorScheme = this.resolvedTheme;
                    }
                }
            });

            Alpine.store('sidebar', {
                isExpanded: false,
                isMobileOpen: false,
                isHovered: false,

                init() {
                    const savedState = localStorage.getItem('sidebarExpanded');
                    if (window.innerWidth >= 1280) {
                        this.isExpanded = savedState === null ? true : savedState === 'true';
                    } else {
                        this.isExpanded = false;
                    }
                    this.isMobileOpen = false;

                    window.addEventListener('resize', () => {
                        this.handleResize();
                    });
                },

                handleResize() {
                    if (window.innerWidth < 1280) {
                        if (this.isMobileOpen) {
                             this.isMobileOpen = false;
                        }
                    } else {
                        this.isMobileOpen = false;
                        const savedState = localStorage.getItem('sidebarExpanded');
                        this.isExpanded = savedState === null ? true : savedState === 'true';
                    }
                },

                toggleExpanded() {
                    this.isExpanded = !this.isExpanded;
                    this.isMobileOpen = false;
                    
                    if (window.innerWidth >= 1280) {
                        localStorage.setItem('sidebarExpanded', this.isExpanded);
                    }
                },

                toggleMobileOpen() {
                    this.isMobileOpen = !this.isMobileOpen;
                },

                setMobileOpen(val) {
                    this.isMobileOpen = val;
                },

                setHovered(val) {
                    if (window.innerWidth >= 1280 && !this.isExpanded) {
                        this.isHovered = val;
                    }
                }
            });
        });
    </script>

    <!-- Apply dark mode immediately to prevent flash -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            const isDark = savedTheme === 'dark';
            if (isDark) {
                document.documentElement.classList.add('dark');
                document.documentElement.setAttribute('data-color-scheme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                document.documentElement.setAttribute('data-color-scheme', 'light');
            }
        })();
    </script>
</head>

<body>
    <x-common.notifications />

    <div class="min-h-screen xl:flex sidebar-expanded" x-data :class="{ 'sidebar-expanded': $store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen }">
        @if ($mobileNavigation)
            <div class="hidden xl:contents">
                @include('layouts.sidebar')
            </div>
        @else
            @include('layouts.backdrop')
            @include('layouts.sidebar')
        @endif

        {{-- transition-all duration-300 ease-in-out --}}
        <div class="flex-1 min-w-0 ml-0 ltr:xl:ml-[90px] rtl:xl:ml-0 rtl:xl:mr-[90px] [.sidebar-expanded_&]:ltr:xl:ml-[290px] [.sidebar-expanded_&]:rtl:xl:ml-0 [.sidebar-expanded_&]:rtl:xl:mr-[290px] transition-all duration-300 ease-in-out">
            <!-- app header start -->
            @include(request()->routeIs('inventory.*') ? 'components.main.inventory.header' : 'layouts.app-header')
            <!-- app header end -->
            <div @class(['mx-auto max-w-(--breakpoint-2xl) p-4 md:px-6 md:pt-6',
                'pb-[calc(5.5rem+env(safe-area-inset-bottom))] xl:pb-6' => $mobileNavigation,
                'max-sm:px-3' => $warehouseNavigation,
                'md:pb-6' => ! $mobileNavigation])>
                @yield('content')
            </div>
        </div>

    </div>
    @if ($mobileNavigation)
        <x-main.inventory.mobile-navigation :warehouse="$warehouseNavigation" :draft="$__env->hasSection('inventory-draft')" />
    @endif

</body>

@stack('scripts')

</html>
