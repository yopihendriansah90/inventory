@props(['step', 'translation', 'screenshots'])

<li data-guide-reveal class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-6 dark:border-gray-800 dark:bg-gray-900">
    <div class="flex items-start gap-3">
        <span class="grid size-8 shrink-0 place-items-center rounded-full bg-brand-50 text-sm font-semibold text-brand-700 dark:bg-brand-500/15 dark:text-brand-300" aria-hidden="true">{{ $step }}</span>
        <div class="min-w-0 pt-0.5">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __($translation.'.title') }}</h3>
            <p class="mt-1 max-w-3xl text-sm leading-relaxed text-gray-600 dark:text-gray-300">{{ __($translation.'.body') }}</p>
        </div>
    </div>
    <div @class(['mt-4 grid gap-4', 'sm:grid-cols-2' => count($screenshots) > 1])>
        @foreach ($screenshots as $file)
            @php
                $image = asset('assets/images/inventory-guide/'.$file.'.png');
                $caption = __('inventory.guide.image.'.$file);
            @endphp
            <figure class="min-w-0">
                <a href="{{ $image }}" target="_blank" rel="noopener"
                    class="block overflow-hidden rounded-xl border border-gray-200 bg-gray-50 hover:border-brand-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-brand-500">
                    <img src="{{ $image }}" alt="{{ $caption }}" width="1440" height="850" loading="lazy" decoding="async" class="block h-auto w-full">
                    <span class="sr-only">{{ __('inventory.guide.new_tab') }}</span>
                </a>
                <figcaption class="mt-2 text-theme-xs text-gray-600 dark:text-gray-300">{{ $caption }}</figcaption>
            </figure>
        @endforeach
    </div>
</li>
