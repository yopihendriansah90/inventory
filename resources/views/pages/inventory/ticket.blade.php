@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb :pageTitle="$ticket->code()" class="max-sm:[&_nav]:hidden" />
    <x-common.component-card :title="__('inventory.ticket.details')">
        <x-main.inventory.receipt-status :status="$ticket->status()" />
        <dl @class(['grid gap-4 sm:grid-cols-3', 'max-sm:grid-cols-2' => ! $warehouse])>
            @foreach ([
                'vendor' => $ticket->vendor->option()['label'],
                'reference' => $ticket->reference,
                'date' => $ticket->received_on->locale(app()->getLocale())->translatedFormat('d M Y'),
            ] as $key => $value)
                <div @class(['min-w-0', 'max-sm:col-span-2' => ! $warehouse && $key === 'vendor'])>
                    <dt class="text-sm text-gray-600 dark:text-gray-300">{{ __('inventory.column.'.$key) }}</dt>
                    <dd class="wrap-anywhere font-medium text-gray-900 dark:text-white">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>

        <x-main.inventory.ticket-lines :ticket="$ticket" :warehouse="$warehouse" />

        <div @class(['flex flex-wrap items-center gap-3', 'max-sm:grid max-sm:gap-2' => $warehouse])>
            @if ($warehouse)
                @unless ($ticket->confirmed_at)
                    <form method="POST" action="{{ route('inventory.ticket.confirm', $ticket) }}">
                        @csrf
                        <x-ui.button type="submit" class="max-sm:w-full">{{ __('inventory.receiving.confirm') }}</x-ui.button>
                    </form>
                @endunless
                <x-ui.button as="a" :href="route('inventory.ticket.warehouse-report', $ticket)" variant="outline">{{ __('inventory.ticket.warehouse_report') }}</x-ui.button>
            @else
                <x-ui.button as="a" :href="route('inventory.ticket.report', [$ticket, 'vendor'])" variant="outline">{{ __('inventory.ticket.vendor_report') }}</x-ui.button>
                @if ($ticket->status() !== 'failed')
                    <x-ui.button as="a" :href="route('inventory.ticket.report', [$ticket, 'warehouse'])" variant="outline">{{ __('inventory.ticket.warehouse_report') }}</x-ui.button>
                    <x-main.inventory.qr-dialog :title="__('inventory.ticket.qr')" :label="$ticket->code().' · '.$ticket->vendor->name"
                        :url="route('inventory.ticket.qr', $ticket)" />
                @endif
            @endif
            <x-ui.button as="a" :href="route($warehouse ? 'inventory.receiving' : 'inventory.qc')" variant="ghost">{{ __('inventory.ticket.back_to_list') }}</x-ui.button>
        </div>
    </x-common.component-card>
@endsection
