@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb :pageTitle="$ticket->code()" class="max-sm:[&_nav]:hidden" />

    @php
        $action = $ticket->warehouseAction();
        $itemsFirst = auth()->user()->can('receive-stock') && in_array($action, ['start', 'pack'], true);
        $packing = $itemsFirst && $action === 'pack';
    @endphp
    <div class="mx-auto max-w-6xl space-y-5">
        @if ($justCreated)
            <x-main.inventory.ticket-steps :labels="['inventory.dispatch.recipient_details', 'inventory.dispatch.purchased_items', 'inventory.dispatch.review_print']" :active="3" />
        @endif
        <div class="flex flex-wrap items-center justify-between gap-3">
            <x-ui.button as="a" :href="route('inventory.dispatch.index')" variant="outline" size="sm">
                {{ __('inventory.dispatch.back') }}
            </x-ui.button>
            @can('request-dispatch')
                @if ($ticket->canCancel())
                    <form method="POST" action="{{ route('inventory.dispatch.cancel', $ticket) }}" x-data
                        x-on:submit.prevent="$confirmAction($el, @js(__('inventory.dispatch.cancel_confirm')), @js(__('inventory.dispatch.cancel')), @js(__('Close')))">
                        @csrf
                        <x-ui.button type="submit" variant="ghost" size="sm">{{ __('inventory.dispatch.cancel') }}</x-ui.button>
                    </form>
                @endif
            @endcan
        </div>

        <section class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('inventory.dispatch.details') }}</h2>
                    <x-main.inventory.dispatch-status :status="$ticket->status" />
                </div>
                @can('receive-stock')
                    @if ($action)
                        <p class="max-w-xl text-sm text-gray-600 dark:text-gray-300">{{ __('inventory.dispatch.guide.'.$action) }}</p>
                    @endif
                @endcan
                @if ($packing)
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ __('inventory.dispatch.packing_total', [
                            'variants' => $ticket->lines->count(),
                            'units' => __('inventory.units', ['count' => $ticket->lines->sum('quantity')]),
                        ]) }}
                    </p>
                @endif
            </div>
            @can('receive-stock')
                @if ($action)
                    <div class="grid shrink-0 gap-2">
                        @if ($action === 'start' || $scanned)
                            <form method="POST" action="{{ route('inventory.dispatch.advance', [$ticket, $action]) }}"
                                @if ($action === 'ship')
                                    x-data x-on:submit.prevent="$confirmAction($el, @js(__('inventory.dispatch.ship_confirm')), @js(__('inventory.dispatch.action.ship')), @js(__('Close')))"
                                @endif>
                                @csrf
                                <x-ui.button type="submit" class="w-full">{{ __('inventory.dispatch.action.'.$action) }}</x-ui.button>
                            </form>
                        @else
                            <x-ui.button as="a" :href="route('inventory.dispatch.scan')">
                                <x-svg.qr-scan class="size-5" aria-hidden="true" />
                                {{ __($action === 'ship' ? 'inventory.dispatch.scan_to_ship' : 'inventory.dispatch.scan') }}
                            </x-ui.button>
                        @endif
                        @if ($packing)
                            <x-ui.button as="a" :href="route('inventory.dispatch.print', [$ticket, 'qr'])" variant="outline">
                                {{ __('inventory.dispatch.print_qr') }}
                            </x-ui.button>
                        @endif
                    </div>
                @endif
            @endcan
        </section>

        <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <div class="min-w-0 space-y-5">
                @if ($itemsFirst)
                    <x-main.inventory.dispatch-items :lines="$ticket->lines" />
                @endif
                <x-common.component-card :title="__('inventory.dispatch.recipient_details')">
                    <dl class="grid gap-4 text-sm sm:grid-cols-2">
                        @foreach (['sales_reference', 'recipient_name', 'recipient_phone', 'recipient_address'] as $field)
                            <div class="min-w-0 rounded-xl bg-gray-50 p-3 dark:bg-gray-800/60">
                                <dt class="mb-1 font-medium text-gray-600 dark:text-gray-300">{{ __('inventory.dispatch.field.'.$field) }}</dt>
                                <dd class="wrap-anywhere whitespace-pre-line text-gray-900 dark:text-white">{{ $ticket->{$field} }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-common.component-card>

                @unless ($itemsFirst)
                    <x-main.inventory.dispatch-items :lines="$ticket->lines" />
                @endunless
            </div>

            <div class="min-w-0 space-y-5">
                <x-common.component-card :title="__('inventory.dispatch.documents')">
                    <div class="grid gap-2">
                        @if ($ticket->status !== \App\Models\InventoryDispatch::CANCELLED)
                            <x-main.inventory.qr-dialog :title="__('inventory.dispatch.qr')" :label="$ticket->code()"
                                :url="route('inventory.dispatch.qr', $ticket)"
                                class="[&_[data-slot=dialog]>button]:w-full [&_[data-slot=dialog]>button]:justify-start" />
                            @unless ($packing)
                                <x-ui.button as="a" :href="route('inventory.dispatch.print', [$ticket, 'qr'])" variant="outline" size="sm" class="justify-start">
                                    {{ __('inventory.dispatch.print_qr') }}
                                </x-ui.button>
                            @endunless
                        @endif
                        <x-ui.button as="a" :href="route('inventory.dispatch.print', [$ticket, 'report'])" variant="outline" size="sm" class="justify-start">
                            {{ __('inventory.dispatch.print_report') }}
                        </x-ui.button>
                    </div>
                </x-common.component-card>
                <x-common.component-card :title="__('inventory.dispatch.timeline')">
                    <ol class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($ticket->events as $event)
                            <li class="space-y-1 py-3 first:pt-0 last:pb-0 text-sm">
                                <x-main.inventory.dispatch-status :status="$event->status" />
                                <time datetime="{{ $event->created_at->toIso8601String() }}" class="block text-gray-700 dark:text-gray-300">
                                    {{ $event->created_at->translatedFormat('d M Y H:i') }}
                                </time>
                                <p class="wrap-anywhere text-gray-600 dark:text-gray-400">{{ $event->user->name }}</p>
                            </li>
                        @endforeach
                    </ol>
                </x-common.component-card>
            </div>
        </div>
    </div>
@endsection
