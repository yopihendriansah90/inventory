@extends('layouts.app')

@section('content')
    @php
        $dispatch = $dispatch ?? false;
        $qc = $qc ?? false;
        $ticketRoute = $dispatch ? 'inventory.dispatch.show' : 'inventory.ticket.receive';
        $ticketParameter = $dispatch ? 'dispatch' : 'ticket';
        $ticketPath = \Illuminate\Support\Str::beforeLast(route($ticketRoute, [$ticketParameter => 'id'], absolute: false), '/');
        [$heading, $lookup, $instruction, $invalid] = match (true) {
            $qc => ['inventory.qc.scan_title', 'inventory.qc.lookup', 'inventory.qc.scan_aim', 'inventory.qc.scan_invalid'],
            $dispatch => ['inventory.dispatch.scan', 'inventory.dispatch.lookup', 'inventory.scan.outbound_aim', 'inventory.dispatch.invalid_qr'],
            default => ['inventory.scan.inbound_title', 'inventory.receiving.lookup', 'inventory.scan.aim', 'inventory.scan.invalid'],
        };
    @endphp
    <x-common.page-breadcrumb :pageTitle="__($heading)" :show-trail="false" />
    <div class="mx-auto max-w-2xl space-y-4">
        @if ($qc)
            <x-ui.button as="a" :href="route('inventory.qc')" variant="outline" size="sm">{{ __('inventory.ticket.back_to_list') }}</x-ui.button>
        @else
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
        @endif
        <x-common.component-card :title="$qc ? null : __('inventory.scan.preview')">
            <div x-data="warehouseScanner(@js([
                'cameraUnavailable' => __('inventory.scan.camera_unavailable'),
                'cameraError' => __('inventory.scan.camera_error'),
                'cameraDenied' => __('inventory.scan.camera_denied'),
                'cameraMissing' => __('inventory.scan.camera_missing'),
                'cameraDisconnected' => __('inventory.scan.camera_disconnected'),
                'imageError' => __('inventory.scan.image_error'),
                'invalidQuery' => __($invalid),
                'lookupError' => __('inventory.scan.lookup_error'),
            ]), @js($ticketPath), @js($dispatch ? 'OUT' : 'QC'))"
                x-on:pagehide.window="stop()" class="space-y-4 sm:space-y-5">
                <div>
                    <div class="relative grid aspect-square max-h-[55dvh] w-full place-items-center overflow-hidden rounded-2xl bg-gray-900 text-white dark:bg-gray-950 sm:aspect-video">
                        <video x-ref="preview" aria-label="{{ __('inventory.scan.preview') }}" autoplay muted playsinline
                            class="absolute inset-0 size-full object-cover sm:object-contain" x-bind:class="{ 'opacity-0': !cameraActive }"></video>
                        <span x-show="!cameraActive" class="text-gray-300">
                            <x-svg.qr-scan class="size-14" />
                        </span>
                        <div x-cloak x-show="cameraActive" aria-hidden="true"
                            class="pointer-events-none absolute aspect-square h-3/5 max-w-[65%] rounded-2xl border-2 border-brand-300 dark:border-brand-300"></div>
                    </div>
                    <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-300">{{ __($instruction) }}</p>
                    <p x-cloak x-show="cameraError" x-text="cameraError" role="alert" class="mt-2 text-sm text-error-600 dark:text-error-400"></p>
                    <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end sm:justify-center">
                        <div x-cloak x-show="cameraActive && cameras.length > 1" class="min-w-0 sm:max-w-xs">
                            <label for="ticket-qr-camera" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('inventory.scan.camera_select') }}</label>
                            <select id="ticket-qr-camera" x-effect="$el.value = cameraId" x-on:change="startCamera($event.target.value)"
                                class="min-h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-start text-sm text-gray-800 focus-visible:outline-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                <option value="">{{ __('inventory.scan.camera_default') }}</option>
                                <template x-for="(camera, index) in cameras" x-bind:key="camera.deviceId">
                                    <option x-bind:value="camera.deviceId" x-text="camera.label || @js(__('inventory.scan.camera_number')).replace(':number', index + 1)" x-bind:selected="camera.deviceId === cameraId"></option>
                                </template>
                            </select>
                        </div>
                        <x-ui.button size="lg" class="w-full sm:w-auto" x-show="!cameraActive" x-on:click="startCamera()" x-bind:disabled="opening || busy || readingImage">
                            <span x-show="!opening">{{ __('inventory.scan.camera') }}</span>
                            <span x-cloak x-show="opening" role="status">{{ __('inventory.scan.opening') }}</span>
                        </x-ui.button>
                        <x-ui.button size="lg" variant="outline" class="w-full sm:w-auto" x-cloak x-show="cameraActive || opening" x-on:click="stopCamera()">
                            {{ __('inventory.scan.stop') }}
                        </x-ui.button>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-4 dark:border-gray-700">
                    <form method="GET" action="{{ route($lookup) }}" x-ref="lookup" x-on:submit.prevent="lookup()"
                        class="grid items-end gap-2 sm:flex sm:flex-wrap sm:gap-3" data-notify-validation="{{ __('inventory.notification.invalid') }}">
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
