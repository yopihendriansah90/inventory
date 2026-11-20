@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb :pageTitle="__('inventory.dispatch.create')" :show-trail="false" />
    @php
        $identityFields = ['sales_reference' => 80, 'recipient_name' => 120, 'recipient_phone' => 32];
        $initialStep = $errors->any() && ! $errors->hasAny([...array_keys($identityFields), 'recipient_address']) ? 2 : 1;
    @endphp
    <x-common.component-card>
        <form method="POST" action="{{ route('inventory.dispatch.store') }}" novalidate
            x-data="dispatchForm(@js($variants), @js(old('lines', [])), @js($errors->getMessages()), @js(__('inventory.dispatch.add_one')), {{ $initialStep }})"
            x-on:submit="submit($event)" data-notify-validation="{{ __('inventory.notification.invalid') }}" class="space-y-5">
            @csrf
            <x-main.inventory.ticket-steps :labels="['inventory.dispatch.recipient_details', 'inventory.dispatch.purchased_items', 'inventory.dispatch.review_print']" />

            <section x-show="step === 1" x-cloak x-ref="identity" data-ticket-step="1" class="space-y-4">
                <h3 tabindex="-1" class="text-base font-semibold text-gray-900 dark:text-white">{{ __('inventory.dispatch.recipient_details') }}</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($identityFields as $field => $limit)
                        <div>
                            <label for="{{ $field }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('inventory.dispatch.field.'.$field) }}
                            </label>
                            <x-main.inventory.input :id="$field" :name="$field" :value="old($field)" :maxlength="$limit" required
                                :type="$field === 'recipient_phone' ? 'tel' : 'text'"
                                :pattern="$field === 'recipient_phone' ? '\+?[0-9][0-9 \(\).\-]*' : null" />
                            <x-main.inventory.field-error :name="$field" />
                        </div>
                    @endforeach
                    <div class="sm:col-span-2">
                        <label for="recipient_address" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('inventory.dispatch.field.recipient_address') }}
                        </label>
                        <textarea id="recipient_address" name="recipient_address" maxlength="1000" rows="3" required
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand-500 focus:outline-hidden focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">{{ old('recipient_address') }}</textarea>
                        <x-main.inventory.field-error name="recipient_address" />
                    </div>
                </div>
            </section>

            <section x-show="step === 2" x-cloak data-ticket-step="2" class="space-y-4">
                <h3 tabindex="-1" class="text-base font-semibold text-gray-900 dark:text-white">{{ __('inventory.dispatch.purchased_items') }}</h3>
                <div class="space-y-4">
                    <div class="max-w-xl">
                        <label for="dispatch-product" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('inventory.search') }}</label>
                        <x-main.inventory.input id="dispatch-product" name="product-picker" type="search" x-ref="picker" x-model="query"
                            x-on:keydown.enter="$event.preventDefault()" autocomplete="off" maxlength="100" />
                    </div>

                    <div x-cloak x-show="query.trim()" class="max-h-60 space-y-2 overflow-y-auto" aria-live="polite">
                        <template x-for="item in matches" :key="item.id">
                            <button type="button" x-on:click="add(item)" x-bind:disabled="item.available <= 0"
                                class="flex w-full flex-wrap items-center justify-between gap-2 rounded-lg border border-gray-200 px-3 py-2 text-start text-sm text-gray-900 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800">
                                <span x-text="item.label"></span>
                                <span class="text-gray-600 dark:text-gray-300">{{ __('inventory.dispatch.stock.available') }} <span x-text="item.available"></span></span>
                            </button>
                        </template>
                        <p x-show="matches.length === 0" class="text-sm text-gray-600 dark:text-gray-300">{{ __('inventory.stock.no_results') }}</p>
                    </div>

                    <x-main.inventory.field-error name="lines" />
                    <template x-for="line in lines" :key="line.id">
                        <div class="flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 p-3 dark:border-gray-700">
                            <div class="min-w-48 grow self-center">
                                <p x-text="line.label" class="text-sm font-medium text-gray-900 dark:text-white"></p>
                                <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('inventory.dispatch.stock.available') }} <span x-text="line.available"></span></p>
                                <p x-show="line.error" x-text="line.error" class="mt-1 text-sm text-error-600 dark:text-error-400"></p>
                            </div>
                            <input type="hidden" x-bind:name="'lines[' + line.id + '][inventory_variant_id]'" x-bind:value="line.id">
                            <div class="w-28">
                                <label x-bind:for="'dispatch-quantity-' + line.id" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('inventory.dispatch.quantity') }}</label>
                                <x-main.inventory.input name="quantity" type="number" min="1" required x-bind:max="Math.min(line.available, 2147483647)" x-model.number="line.quantity"
                                    x-bind:id="'dispatch-quantity-' + line.id" x-bind:name="'lines[' + line.id + '][quantity]'" />
                            </div>
                            <x-ui.button type="button" variant="outline" x-on:click="remove(line.id)">{{ __('inventory.dispatch.remove') }}</x-ui.button>
                        </div>
                    </template>

                </div>
            </section>

            <section x-show="step === 3" x-cloak data-ticket-step="3" class="space-y-4">
                <h3 tabindex="-1" class="text-base font-semibold text-gray-900 dark:text-white">{{ __('inventory.dispatch.review_print') }}</h3>
                <dl class="grid gap-4 rounded-xl border border-gray-200 p-4 text-sm dark:border-gray-700 sm:grid-cols-2">
                    @foreach ([...array_keys($identityFields), 'recipient_address'] as $field)
                        <div>
                            <dt class="font-medium text-gray-600 dark:text-gray-300">{{ __('inventory.dispatch.field.'.$field) }}</dt>
                            <dd x-text="preview.{{ $field }}" class="wrap-anywhere whitespace-pre-line text-gray-900 dark:text-white"></dd>
                        </div>
                    @endforeach
                </dl>
                <ul class="divide-y divide-gray-200 rounded-xl border border-gray-200 px-4 dark:divide-gray-700 dark:border-gray-700">
                    <template x-for="line in lines" :key="line.id">
                        <li class="flex justify-between gap-4 py-3 text-sm text-gray-900 dark:text-white">
                            <span x-text="line.label" class="wrap-anywhere"></span>
                            <span x-text="line.quantity" class="shrink-0 font-semibold tabular-nums"></span>
                        </li>
                    </template>
                </ul>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('inventory.dispatch.print_after_save') }}</p>
            </section>

            <div class="flex flex-wrap justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
                <x-ui.button as="a" :href="route('inventory.dispatch.index')" variant="outline" x-cloak="x-cloak" x-show="step === 1">{{ __('inventory.dispatch.back') }}</x-ui.button>
                <x-ui.button variant="outline" x-cloak="x-cloak" x-show="step > 1" x-on:click="goTo(step - 1)">{{ __('inventory.ticket.back') }}</x-ui.button>
                <x-ui.button x-cloak="x-cloak" x-show="step < 3" x-on:click="next()">{{ __('inventory.ticket.next') }}</x-ui.button>
                <x-ui.button type="submit" x-cloak="x-cloak" x-show="step === 3">{{ __('inventory.dispatch.submit') }}</x-ui.button>
            </div>
        </form>
    </x-common.component-card>
@endsection
