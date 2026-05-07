<div>
    <flux:heading size="xl" class="text-center">{{ __('Welcome back') }}</flux:heading>
    <flux:text class="mt-2 text-center">{{ __('Sign in to keep praying with us.') }}</flux:text>

    @if (session('status'))
        <div class="mt-6 rounded-md bg-emerald-50 p-3 text-sm font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="login" class="mt-8 space-y-5">
        <flux:input wire:model="form.email" :label="__('Email')" type="email" required autofocus autocomplete="username" />

        <flux:input wire:model="form.password" :label="__('Password')" type="password" required autocomplete="current-password" />

        <flux:checkbox wire:model="form.remember" :label="__('Remember me')" />

        <div class="flex items-center justify-between gap-4 pt-2">
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-[#c8202f] hover:underline dark:text-rose-300" wire:navigate>
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <flux:button type="submit" variant="primary">
                {{ __('Log in') }}
            </flux:button>
        </div>

        <p class="pt-4 text-center text-sm text-zinc-600 dark:text-zinc-400">
            {{ __("Don't have an account?") }}
            <a href="{{ route('register') }}" class="font-medium text-[#c8202f] hover:underline dark:text-rose-300" wire:navigate>{{ __('Sign up') }}</a>
        </p>
    </form>
</div>