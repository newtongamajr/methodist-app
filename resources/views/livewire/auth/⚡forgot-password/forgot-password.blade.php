<div>
    <flux:heading size="xl" class="text-center">{{ __('Reset password') }}</flux:heading>
    <flux:text class="mt-2 text-center">
        {{ __('Forgot your password? Enter your email and we will send you a link to reset it.') }}
    </flux:text>

    @if (session('status'))
        <div class="mt-6 rounded-md bg-emerald-50 p-3 text-sm font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="sendPasswordResetLink" class="mt-8 space-y-5">
        <flux:input wire:model="email" :label="__('Email')" type="email" required autofocus />

        <div class="flex items-center justify-between gap-4 pt-2">
            <a href="{{ route('login') }}" class="text-sm font-medium text-[#c8202f] hover:underline dark:text-rose-300" wire:navigate>
                {{ __('Back to login') }}
            </a>
            <flux:button type="submit" variant="primary">
                {{ __('Email password reset link') }}
            </flux:button>
        </div>
    </form>
</div>