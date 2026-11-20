@extends('layouts.app')

@section('content')
    @php
        $dispatch = $dispatch ?? false;
        $ticketRoute = $dispatch ? 'inventory.dispatch.show' : 'inventory.ticket.receive';
        $ticketParameter = $dispatch ? 'dispatch' : 'ticket';
        $ticketPath = \Illuminate\Support\Str::beforeLast(route($ticketRoute, [$ticketParameter => 'id'], absolute: false), '/');
    @endphp
    <x-common.page-breadcrumb :pageTitle="__($dispatch ? 'inventory.dispatch.scan' : 'inventory.scan.inbound_title')" :show-trail="false" />
    <div class="mx-auto max-w-2xl space-y-4">
        <nav aria-label="{{ __('inventory.scan.mode') }}" class="grid grid-cols-2 gap-1 rounded-xl border border-gray-200 bg-white p-1 dark:border-gray-800 dark:bg-gray-900">
            @foreach (['inventory.receiving.scan' => 'inventory.scan.inbound', 'inventory.dispatch.scan' => 'inventory.dispatch.nav'] as $route => $label)
                @php($active = request()->routeIs($route))
                <a href="{{ route($route) }}" @if ($active) aria-current="page" @endif
                    @class([
                        'flex min-h-11 items-center justify-center rounded-lg px-2 py-1.5 text-center text-sm font-medium focus-visible:outline-brand-500',
                        'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300' => $active,
                        'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800' => ! $active,
                    ])>{{ __($label) }}</a>
            @endforeach
        </nav>
        <x-common.component-card :title="__('inventory.scan.preview')">
            <div x-data="warehouseScanner(@js([
                'cameraUnavailable' => __('inventory.scan.camera_unavailable'),
                'cameraError' => __('inventory.scan.camera_error'),
                'imageError' => __('inventory.scan.image_error'),
                'invalidQuery' => __($dispatch ? 'inventory.dispatch.invalid_qr' : 'inventory.scan.invalid'),
                'lookupError' => __('inventory.scan.lookup_error'),
            ]), @js($ticketPath), @js($dispatch ? 'OUT' : 'QC'))"
                x-on:pagehide.window="stop()" class="space-y-5">
                <div class="mx-auto max-w-md">
                    <div class="relative grid aspect-video place-items-center overflow-hidden rounded-2xl bg-gray-900 text-white dark:bg-gray-950">
                        <video x-ref="preview" aria-label="{{ __('inventory.scan.preview') }}" autoplay muted playsinline
                            class="absolute inset-0 size-full object-contain" x-bind:class="{ 'opacity-0': !cameraActive }"></video>
                        <span x-show="!cameraActive" class="text-gray-300">
                            <x-svg.qr-scan class="size-14" />
                        </span>
                        <div x-cloak x-show="cameraActive" aria-hidden="true"
                            class="pointer-events-none absolute aspect-square h-3/4 rounded-2xl border-2 border-brand-300 dark:border-brand-300"></div>
                    </div>
                    <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-300">{{ __($dispatch ? 'inventory.scan.outbound_aim' : 'inventory.scan.aim') }}</p>
                    <div class="mt-3 flex justify-center">
                        <x-ui.button size="lg" x-show="!cameraActive" x-on:click="startCamera()" x-bind:disabled="opening || busy || readingImage">
                            <span x-show="!opening">{{ __('inventory.scan.camera') }}</span>
                            <span x-cloak x-show="opening">{{ __('inventory.scan.opening') }}</span>
                        </x-ui.button>
                        <x-ui.button size="lg" variant="outline" x-cloak x-show="cameraActive" x-on:click="stopCamera()">
                            {{ __('inventory.scan.stop') }}
                        </x-ui.button>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-4 dark:border-gray-700">
                    <form method="GET" action="{{ route($dispatch ? 'inventory.dispatch.lookup' : 'inventory.receiving.lookup') }}" x-ref="lookup" x-on:submit.prevent="lookup()"
                        class="flex flex-wrap items-end gap-3" data-notify-validation="{{ __('inventory.notification.invalid') }}">
                        <div class="min-w-0 grow">
                            <label for="ticket-qr-query" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('inventory.scan.input') }}</label>
                            <x-main.inventory.input id="ticket-qr-query" name="q" x-ref="query" :value="old('q')"
                                :placeholder="__($dispatch ? 'inventory.dispatch.placeholder' : 'inventory.scan.placeholder')" maxlength="2048" autocomplete="off" required />
                            <x-main.inventory.field-error name="q" />
                        </div>
                        <x-ui.button type="submit" variant="outline" class="h-11" x-bind:disabled="busy">
                            <span x-show="!busy">{{ __('inventory.scan.open') }}</span>
                            <span x-cloak x-show="busy" role="status">{{ __('inventory.scan.busy') }}</span>
                        </x-ui.button>
                    </form>
                    <div class="mt-3">
                        <label for="ticket-qr-image" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('inventory.scan.image') }}</label>
                        <x-main.inventory.input id="ticket-qr-image" name="image" type="file" accept="image/*"
                            class="w-full max-w-md pt-2" x-on:change="readImage($el)" x-bind:disabled="busy || readingImage || opening" />
                    </div>
                </div>
            </div>
        </x-common.component-card>
    </div>
@endsection
