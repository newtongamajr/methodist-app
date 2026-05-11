<div>
    <flux:heading size="xl" class="text-center">{{ __('Reset password') }}</flux:heading>
    <flux:text class="mt-2 text-center">
        {{ __('Forgot your password? Enter your email and we will send you a link to reset it.') }}
    </flux:text>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle" inline class="mt-6" :heading="session('status')" />
    @endif

    <form wire:submit="sendPasswordResetLink" class="mt-8 space-y-5">
        <flux:input wire:model="email" :label="__('Email')" type="email" required autofocus />

        <div class="flex items-center justify-between gap-4 pt-2">
            <a href="{{ route('login') }}" class="text-sm font-medium text-accent hover:underline dark:text-rose-300" wire:navigate>
                {{ __('Back to login') }}
            </a>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="sendPasswordResetLink">
                {{ __('Email password reset link') }}
            </flux:button>
        </div>
    </form>
</div>