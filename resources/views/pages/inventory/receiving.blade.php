@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb :pageTitle="__('inventory.receiving.title')" class="max-sm:[&_nav]:hidden" />
    <x-common.component-card :title="__('inventory.receiving.card')">
        @if ($pending)
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.badge color="gray">{{ __('inventory.dashboard.warehouse.inbound') }}</x-ui.badge>
                <x-ui.button as="a" :href="route('inventory.receiving')" variant="outline" size="sm">{{ __('inventory.dashboard.clear_filter') }}</x-ui.button>
            </div>
        @endif
        <div class="hidden xl:flex xl:justify-end">
            <x-ui.button as="a" :href="route('inventory.receiving.scan')">
                <x-svg.qr-scan class="size-5" />
                {{ __('inventory.warehouse.scan') }}
            </x-ui.button>
        </div>
        <x-main.inventory.tickets-table :tickets="$tickets" warehouse />
    </x-common.component-card>
@endsection
