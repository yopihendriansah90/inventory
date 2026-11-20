{{-- Combobox root — behaviour in resources/js/ui/combobox.js. Compose with input / content / item / empty. Dispatches `change` with { value }. --}}
@props(['defaultValue' => null, 'clearOnInput' => false])
<div data-slot="combobox" data-ui-root x-data="uiCombobox({{ \Illuminate\Support\Js::from(['value' => $defaultValue, 'clearOnInput' => $clearOnInput]) }})" {{ $attributes->merge(['class' => 'contents']) }}>{{ $slot }}</div>
