@props(['title', 'label', 'url'])

<div x-data="{ qrLabel: @js($label), qrUrl: @js($url), imageFailed: false }" {{ $attributes }}>
    <x-ui.dialog>
        <x-ui.button variant="outline" size="sm" x-ref="trigger" x-on:click="imageFailed = false; show()"
            aria-haspopup="dialog" x-bind:aria-expanded="open.toString()">
            {{ $title }}
        </x-ui.button>

        <x-ui.dialog.content class="max-h-[calc(100dvh-2rem)] overflow-y-auto sm:max-w-sm" aria-modal="true">
            <x-ui.dialog.header>
                <x-ui.dialog.title>{{ $title }}</x-ui.dialog.title>
                <x-ui.dialog.description class="break-words" x-text="open ? qrLabel : ''">{{ $label }}</x-ui.dialog.description>
            </x-ui.dialog.header>

            <div x-show="!imageFailed" class="mx-auto flex w-full max-w-64 justify-center rounded-lg bg-white">
                <img x-bind:src="open && qrUrl ? qrUrl : null" x-on:error="if (open) { imageFailed = true; $notify('error', $el.dataset.errorMessage) }"
                    data-error-message="{{ __('inventory.qr.preview_error') }}"
                    alt="{{ __('inventory.qr.image') }}" width="256" height="256" class="h-auto w-full">
            </div>
            <x-ui.dialog.footer>
                <x-ui.button as="a" x-bind:href="qrUrl ? qrUrl + '?download=1' : null" download>
                    {{ __('inventory.qr.download') }}
                </x-ui.button>
            </x-ui.dialog.footer>
        </x-ui.dialog.content>
    </x-ui.dialog>
</div>
