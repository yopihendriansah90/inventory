@extends('layouts.fullscreen-layout')

@section('content')
    @php
        $roles = [
            'qc' => [
                'icon' => 'task',
                'steps' => [
                    'vendor' => ['qc-vendor', 'qc-vendor-baru'],
                    'items' => ['qc-barang', 'qc-variant-baru'],
                    'review' => ['qc-tinjau'],
                    'documents' => ['qc-dokumen', 'qc-qr'],
                ],
            ],
            'gudang' => [
                'icon' => 'box',
                'steps' => [
                    'receive' => ['gudang-pindai', 'gudang-terima'],
                    'start' => ['gudang-mulai-packing', 'gudang-label'],
                    'pack' => ['gudang-selesai-packing'],
                    'ship' => ['gudang-barang-keluar'],
                ],
            ],
            'cs' => [
                'icon' => 'chat',
                'steps' => [
                    'stock' => ['cs-stok'],
                    'recipient' => ['cs-penerima'],
                    'items' => ['cs-barang', 'cs-tinjau'],
                    'track' => ['cs-dokumen', 'cs-qr', 'cs-cari-tiket', 'cs-lacak'],
                ],
            ],
        ];
    @endphp

    <header id="guide-top" class="border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-6">
            <a href="#guide-top" class="inline-flex items-center gap-2.5 font-semibold text-gray-900 dark:text-white">
                <span class="grid size-9 place-items-center rounded-xl bg-brand-600 text-white">
                    <x-svg.box class="size-5" aria-hidden="true" />
                </span>
                {{ __('inventory.title') }}
            </a>
            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                <x-main.inventory.language compact />
                <x-ui.button as="a" :href="auth()->check() ? route('inventory.home') : route('signin')" variant="outline" size="sm">
                    {{ __(auth()->check() ? 'inventory.dashboard.title' : 'inventory.login.title') }}
                </x-ui.button>
            </div>
        </div>
    </header>

    <main data-inventory-guide class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-10">
        <div class="overflow-hidden rounded-3xl bg-brand-900 px-6 py-10 text-white sm:px-10 sm:py-14 dark:bg-brand-950">
            <div class="grid items-end gap-8 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
                <div class="space-y-4">
                    <h1 class="max-w-xl text-3xl font-semibold tracking-tight text-white sm:text-5xl">{{ __('inventory.guide.heading') }}</h1>
                    <p class="max-w-xl text-sm leading-relaxed text-brand-100 sm:text-base">{{ __('inventory.guide.description') }}</p>
                </div>
                <ol class="grid gap-2 rounded-2xl border border-white/15 bg-white/10 p-4 text-sm font-medium text-white sm:p-5">
                    @foreach ($roles as $role => $details)
                        <li class="flex items-center gap-3">
                            <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-white/15">
                                <x-dynamic-component :component="'svg.'.$details['icon']" class="size-4" aria-hidden="true" />
                            </span>
                            {{ __('inventory.guide.'.$role.'.workflow') }}
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>

        <nav aria-label="{{ __('inventory.guide.select_role') }}" class="mt-6 grid gap-3 sm:grid-cols-3">
            @foreach ($roles as $role => $details)
                <a href="#{{ $role }}" class="group flex min-w-0 items-center gap-3 rounded-2xl border border-gray-200 bg-white p-4 hover:border-brand-300 hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-brand-600 dark:hover:bg-brand-500/10">
                    <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300">
                        <x-dynamic-component :component="'svg.'.$details['icon']" class="size-5" aria-hidden="true" />
                    </span>
                    <span class="min-w-0 grow">
                        <span class="block text-sm font-semibold text-gray-900 dark:text-white">{{ __('inventory.guide.'.$role.'.label') }}</span>
                        <span class="mt-0.5 block text-theme-xs text-gray-600 dark:text-gray-300">{{ __('inventory.guide.'.$role.'.summary') }}</span>
                    </span>
                    <x-svg.arrow-right class="size-4 shrink-0 text-gray-500 group-hover:text-brand-600 rtl:rotate-180 dark:text-gray-400 dark:group-hover:text-brand-300" aria-hidden="true" />
                </a>
            @endforeach
        </nav>

        <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">{{ __('inventory.guide.screenshot_note') }}</p>

        @foreach ($roles as $role => $details)
            <section id="{{ $role }}" aria-labelledby="{{ $role }}-title" class="mt-12 scroll-mt-6 sm:mt-16">
                <div data-guide-reveal class="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <h2 id="{{ $role }}-title" class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">{{ __('inventory.guide.'.$role.'.label') }}</h2>
                    <a href="#guide-top" class="inline-flex items-center gap-1.5 rounded text-sm font-medium text-brand-700 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500 dark:text-brand-300">
                        <x-svg.arrow-up class="size-4" aria-hidden="true" />
                        {{ __('inventory.guide.top') }}
                    </a>
                </div>
                <ol class="space-y-4 sm:space-y-5">
                    @foreach ($details['steps'] as $step => $screenshots)
                        <x-main.inventory.guide-step :step="$loop->iteration" :translation="'inventory.guide.'.$role.'.'.$step" :screenshots="$screenshots" />
                    @endforeach
                </ol>
            </section>
        @endforeach
    </main>
@endsection
