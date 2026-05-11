<div>
    <flux:heading size="xl" class="text-center">{{ __('Reset password') }}</flux:heading>

    <form wire:submit="resetPassword" class="mt-8 space-y-5">
        <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="username" />
        <flux:input wire:model="password" :label="__('Password')" type="password" required autocomplete="new-password" />
        <flux:input wire:model="password_confirmation" :label="__('Confirm Password')" type="password" required autocomplete="new-password" />

        <div class="flex justify-end pt-2">
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="resetPassword">
                {{ __('Reset password') }}
            </flux:button>
        </div>
    </form>
</div>