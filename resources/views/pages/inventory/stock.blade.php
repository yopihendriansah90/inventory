@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb :pageTitle="__('inventory.stock.title')" />
    @can('request-dispatch')
        <div class="mb-4 flex flex-wrap justify-end gap-3">
            <x-ui.button as="a" :href="route('inventory.dispatch.index')" variant="outline">{{ __('inventory.dispatch.tracking') }}</x-ui.button>
            <x-ui.button as="a" :href="route('inventory.dispatch.create')">{{ __('inventory.dispatch.create') }}</x-ui.button>
        </div>
    @endcan
    <x-common.component-card :title="__('inventory.stock.card')">
        <x-main.inventory.stock-browser :variants="$variants" :search="$search" />
    </x-common.component-card>
@endsection
