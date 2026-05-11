<section>
    <header>
        <flux:heading size="lg">{{ __('Identity') }}</flux:heading>
        <flux:text class="mt-1">{{ __("Your name and email address.") }}</flux:text>
    </header>

    <form wire:submit="updateIdentity" class="mt-6 space-y-5">
        <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

        <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="username" />

        @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
            <flux:text class="text-sm text-amber-700 dark:text-amber-300">
                {{ __('Your email address is unverified.') }}
                <button type="button" wire:click.prevent="sendVerification" class="ms-1 underline">
                    {{ __('Click here to re-send the verification email.') }}
                </button>
            </flux:text>
            @if (session('status') === 'verification-link-sent')
                <flux:text class="text-sm text-emerald-600 dark:text-emerald-400">
                    {{ __('A new verification link has been sent to your email address.') }}
                </flux:text>
            @endif
        @endif

        <div class="flex items-center gap-4 pt-2">
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="updateIdentity">{{ __('Save') }}</flux:button>
            <x-action-message class="text-sm text-emerald-600 dark:text-emerald-400" on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>