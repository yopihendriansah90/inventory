@extends('layouts.fullscreen-layout')

@section('content')
    @php($warehouse = $copy === 'warehouse')
    <main class="min-h-screen px-4 py-6 print:bg-white print:p-0" x-data>
        <div class="mx-auto mb-4 flex max-w-4xl flex-wrap justify-between gap-2 print:hidden">
            <x-ui.button as="a" :href="route(request()->routeIs('inventory.ticket.warehouse-report') ? 'inventory.ticket.receive' : 'inventory.ticket.show', $ticket)"
                variant="outline">{{ __('inventory.ticket.back_to_ticket') }}</x-ui.button>
            <div class="flex flex-wrap items-center gap-2">
                <x-main.inventory.language />
                <x-ui.button x-on:click="window.print()">{{ __('inventory.ticket.print') }}</x-ui.button>
            </div>
        </div>
        <article class="mx-auto max-w-4xl space-y-5 rounded-xl border border-gray-200 bg-white p-4 text-gray-900 sm:p-8 print:max-w-none print:rounded-none print:border-0">
            <header class="space-y-1">
                <p class="text-sm">{{ __('inventory.title') }} · {{ $ticket->code() }}</p>
                <h1 class="text-xl font-semibold">{{ __($warehouse ? 'inventory.ticket.warehouse_report_title' : 'inventory.ticket.vendor_report_title') }}</h1>
                <p class="text-sm">{{ __('inventory.status.'.$ticket->status()) }}</p>
            </header>
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                @foreach ([
                    __('inventory.column.vendor') => $ticket->vendor->option()['label'],
                    __('inventory.column.reference') => $ticket->reference,
                    __('inventory.column.date') => $ticket->received_on->locale(app()->getLocale())->translatedFormat('d M Y'),
                    __('inventory.ticket.inspected_by') => $ticket->inspector->name,
                ] as $label => $value)
                    <div>
                        <dt>{{ $label }}</dt>
                        <dd class="wrap-anywhere font-semibold">{{ $value }}</dd>
                    </div>
                @endforeach
                @if ($ticket->confirmed_at)
                    <div>
                        <dt>{{ __('inventory.ticket.confirmed_by') }}</dt>
                        <dd class="font-semibold">{{ $ticket->receiver->name }} · {{ $ticket->confirmed_at->locale(app()->getLocale())->translatedFormat('d M Y H:i') }}</dd>
                    </div>
                @endif
            </dl>
            <x-main.inventory.ticket-lines :ticket="$ticket" :warehouse="$warehouse" printing />
        </article>
    </main>
@endsection
