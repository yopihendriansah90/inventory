@props(['labels', 'active' => null])

<ol class="grid grid-cols-3 gap-2 rounded-xl bg-gray-50 p-2 text-xs dark:bg-gray-800 sm:text-sm" aria-label="{{ __('inventory.ticket.steps') }}">
    @foreach ($labels as $label)
        @php($number = $loop->iteration)
        <li @if ($active === null)
                x-bind:aria-current="step === {{ $number }} ? 'step' : null"
                x-bind:class="step === {{ $number }} ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300' : 'text-gray-600 dark:text-gray-300'"
            @elseif ($active === $number)
                aria-current="step"
            @endif
            @class([
                'flex flex-wrap items-center gap-2 rounded-lg p-2 font-medium sm:px-3',
                'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300' => $active === $number,
                'text-gray-600 dark:text-gray-300' => $active !== null && $active !== $number,
            ])>
            <span class="grid size-6 shrink-0 place-items-center rounded-full border border-current">{{ $number }}</span>
            <span>{{ __($label) }}</span>
        </li>
    @endforeach
</ol>
