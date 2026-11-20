@props(['name', 'bag' => 'default'])

@error($name, $bag)
    <p class="mt-1 text-sm text-error-600 dark:text-error-400">{{ $message }}</p>
@enderror
