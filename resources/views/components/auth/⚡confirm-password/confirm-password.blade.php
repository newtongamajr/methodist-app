<div>
    <flux:heading size="xl" class="text-center">{{ __('Confirm your password') }}</flux:heading>
    <flux:text class="mt-2 text-center">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </flux:text>

    <form wire:submit="confirmPassword" class="mt-8 space-y-5">
        <flux:input wire:model="password" :label="__('Password')" type="password" required autofocus autocomplete="current-password" />

        <div class="flex justify-end pt-2">
            <flux:button type="submit" variant="primary">{{ __('Confirm') }}</flux:button>
        </div>
    </form>
</div>