@extends('layouts.fullscreen-layout')

@section('content')
    <main class="grid min-h-screen place-items-center px-4 py-8">
        <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm sm:p-8 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-sm font-semibold text-brand-700 dark:text-brand-300">{{ __('inventory.title') }}</p>
                <x-main.inventory.language />
            </div>
            <h1 class="mt-2 text-title-sm font-semibold text-gray-900 dark:text-white">{{ __('inventory.login.title') }}</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ __('inventory.login.description') }}</p>

            <form method="POST" action="{{ route('signin.store') }}" class="mt-6 space-y-4" data-notify-validation="{{ __('inventory.notification.invalid') }}">
                @csrf
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('inventory.login.email') }}</label>
                    <input id="email" name="email" type="email" autocomplete="username" value="{{ old('email') }}" required
                        @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                        class="h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-900 focus:border-brand-500 focus:outline-hidden focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    @error('email')
                        <p id="email-error" class="mt-1 text-sm text-error-600 dark:text-error-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('inventory.login.password') }}</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                        @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                        class="h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-900 focus:border-brand-500 focus:outline-hidden focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    @error('password')
                        <p id="password-error" class="mt-1 text-sm text-error-600 dark:text-error-400">{{ $message }}</p>
                    @enderror
                </div>
                <x-ui.button type="submit" size="lg" class="w-full">{{ __('inventory.login.title') }}</x-ui.button>
            </form>
        </div>
    </main>
@endsection
