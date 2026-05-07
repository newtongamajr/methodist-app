<div>
    <flux:heading size="xl" class="text-center">{{ __('Verify your email') }}</flux:heading>
    <flux:text class="mt-2 text-center">
        {{ __("Thanks for signing up! Before getting started, please confirm your email by clicking the link we just emailed to you. If you didn't receive the email, we will gladly send you another.") }}
    </flux:text>

    @if (session('status') == 'verification-link-sent')
        <div class="mt-6 rounded-md bg-emerald-50 p-3 text-center text-sm font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-8 flex items-center justify-between gap-4">
        <flux:button wire:click="logout" variant="ghost" size="sm">{{ __('Log out') }}</flux:button>
        <flux:button wire:click="sendVerification" variant="primary">{{ __('Resend verification email') }}</flux:button>
    </div>
</div>