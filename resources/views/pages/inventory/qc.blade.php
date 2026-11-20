@extends('layouts.app')

@if (request()->boolean('new') || $selectedSku !== null || $errors->any() || $errors->variant->any())
    @section('inventory-draft', 'true')
@endif

@section('content')
    <x-common.page-breadcrumb :pageTitle="__('inventory.qc.title')" />

    <div class="space-y-6">
        @hasSection('inventory-draft')
            <x-ui.button as="a" :href="route('inventory.qc')" variant="outline">{{ __('inventory.ticket.back_to_list') }}</x-ui.button>
            <x-common.component-card id="inspection" class="scroll-mt-28" :title="__('inventory.qc.form')">
                <x-main.inventory.qc-form :variants="$variants" :vendors="$vendors" :selected-sku="$selectedSku" />
            </x-common.component-card>
        @else
            <x-ui.button as="a" :href="route('inventory.qc', ['new' => 1]).'#inspection'">{{ __('inventory.qc.form') }}</x-ui.button>

            <x-common.component-card :title="__('inventory.qc.card')">
                @if ($date || $result)
                    <div class="flex flex-wrap items-center gap-3">
                        @if ($date)
                            <x-ui.badge color="gray">{{ \Illuminate\Support\Carbon::parse($date)->locale(app()->getLocale())->translatedFormat('d M Y') }}</x-ui.badge>
                        @endif
                        @if ($result)
                            <x-ui.badge color="gray">{{ __('inventory.dashboard.qc.'.$result) }}</x-ui.badge>
                        @endif
                        <x-ui.button as="a" :href="route('inventory.qc')" variant="outline" size="sm">{{ __('inventory.dashboard.clear_filter') }}</x-ui.button>
                    </div>
                @endif
                <x-main.inventory.tickets-table :tickets="$tickets" />
            </x-common.component-card>
        @endif
    </div>
@endsection
