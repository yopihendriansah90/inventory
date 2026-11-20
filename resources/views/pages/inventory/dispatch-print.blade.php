@extends('layouts.fullscreen-layout')

@section('content')
    <main class="min-h-screen px-4 py-6 print:bg-white print:p-0" x-data>
        <div class="mx-auto mb-4 flex max-w-4xl flex-wrap justify-between gap-2 print:hidden">
            <x-ui.button as="a" :href="route('inventory.dispatch.show', $ticket)" variant="outline">
                {{ __('inventory.ticket.back_to_ticket') }}
            </x-ui.button>
            <x-ui.button x-on:click="window.print()">{{ __('inventory.ticket.print') }}</x-ui.button>
        </div>

        @if ($labelOnly)
            <article class="mx-auto w-fit break-inside-avoid rounded-xl border border-gray-200 bg-white p-4 text-center text-gray-900 print:mx-0 print:rounded-none">
                <h1 class="text-lg font-semibold">{{ $ticket->code() }}</h1>
                <img src="{{ route('inventory.dispatch.qr', $ticket) }}" alt="{{ __('inventory.dispatch.qr') }}"
                    width="192" height="192" class="size-48">
            </article>
        @else
            <article class="mx-auto max-w-4xl space-y-5 rounded-xl border border-gray-200 bg-white p-4 text-gray-900 sm:p-8 print:max-w-none print:rounded-none print:border-0">
                <header class="space-y-1">
                    <h1 class="text-xl font-semibold">{{ __('inventory.dispatch.report') }}</h1>
                    <p class="text-sm">{{ $ticket->code() }} · {{ __('inventory.dispatch.status.'.$ticket->status) }}</p>
                </header>
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    @foreach (['sales_reference', 'recipient_name', 'recipient_phone', 'recipient_address'] as $field)
                        <div class="break-inside-avoid">
                            <dt>{{ __('inventory.dispatch.field.'.$field) }}</dt>
                            <dd class="wrap-anywhere whitespace-pre-line font-semibold">{{ $ticket->{$field} }}</dd>
                        </div>
                    @endforeach
                </dl>
                <div>
                    <h2 class="mb-2 font-semibold">{{ __('inventory.dispatch.purchased_items') }}</h2>
                    <table class="w-full text-start text-sm">
                        <thead class="border-y border-gray-300">
                            <tr>
                                <th scope="col" class="px-2 py-2 text-start">{{ __('inventory.column.sku') }}</th>
                                <th scope="col" class="px-2 py-2 text-start">{{ __('inventory.column.model') }}</th>
                                <th scope="col" class="px-2 py-2 text-end">{{ __('inventory.dispatch.quantity') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($ticket->lines as $line)
                                <tr class="break-inside-avoid">
                                    <td class="wrap-anywhere px-2 py-2">{{ $line->variant->displaySku() }}</td>
                                    <td class="wrap-anywhere px-2 py-2">{{ $line->variant->description() }}</td>
                                    <td class="px-2 py-2 text-end">{{ $line->quantity }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div>
                    <h2 class="mb-2 font-semibold">{{ __('inventory.dispatch.timeline') }}</h2>
                    <ol class="space-y-1 text-sm">
                        @foreach ($ticket->events as $event)
                            <li class="break-inside-avoid">
                                {{ __('inventory.dispatch.status.'.$event->status) }} · {{ $event->created_at->locale(app()->getLocale())->translatedFormat('d M Y H:i') }} · {{ $event->user->name }}
                            </li>
                        @endforeach
                    </ol>
                </div>
            </article>
        @endif
    </main>
@endsection
