@props(['compact' => false, 'variant' => 'outline'])

@php
    $languages = ['en' => 'English', 'id' => 'Bahasa Indonesia'];
    $locale = app()->getLocale();
@endphp

<div x-data>
    <form method="POST" action="{{ route('inventory.language') }}" x-ref="languageForm">
        @csrf
        <input type="hidden" name="return_to" value="{{ request()->getRequestUri() }}">
        <input type="hidden" name="locale" disabled>
        <noscript>
            <select name="locale" aria-label="{{ __('inventory.language') }}">
                @foreach ($languages as $code => $label)
                    <option value="{{ $code }}" lang="{{ $code }}" @selected($locale === $code)>{{ $label }}</option>
                @endforeach
            </select>
            <x-ui.button type="submit" size="sm" :variant="$variant">{{ __('inventory.language.apply') }}</x-ui.button>
        </noscript>
    </form>

    <div x-cloak>
        <x-ui.dropdown-menu>
            <x-ui.dropdown-menu.trigger as-child>
                <x-ui.button :variant="$variant" size="sm" :aria-label="__('inventory.language').': '.$languages[$locale]">
                    <x-svg.globe class="size-4" aria-hidden="true" />
                    <span lang="{{ $locale }}" @class(['hidden sm:inline' => $compact])>{{ $languages[$locale] }}</span>
                    <x-svg.chevron-down class="size-4" aria-hidden="true" />
                </x-ui.button>
            </x-ui.dropdown-menu.trigger>

            <x-ui.dropdown-menu.content align="end" class="min-w-48 p-2">
                @foreach ($languages as $code => $label)
                    <x-ui.dropdown-menu.item :lang="$code" :data-locale="$code" :aria-current="$locale === $code ? 'true' : null"
                        :class="$locale === $code ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : ''"
                        @click="$refs.languageForm.elements.locale.value = $el.dataset.locale; $refs.languageForm.elements.locale.disabled = false; $refs.languageForm.requestSubmit()">
                        <span class="grow">{{ $label }}</span>
                        @if ($locale === $code)
                            <x-svg.check class="size-4" aria-hidden="true" />
                        @endif
                    </x-ui.dropdown-menu.item>
                @endforeach
            </x-ui.dropdown-menu.content>
        </x-ui.dropdown-menu>
    </div>
</div>
