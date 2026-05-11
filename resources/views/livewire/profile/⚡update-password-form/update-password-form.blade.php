<section>
    <header>
        <flux:heading size="lg">{{ __('Update Password') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Ensure your account is using a long, random password to stay secure.') }}</flux:text>
    </header>

    <form wire:submit="updatePassword" class="mt-6 space-y-5">
        <flux:input wire:model="current_password" :label="__('Current Password')" type="password" autocomplete="current-password" />
        <flux:input wire:model="password" :label="__('New Password')" type="password" autocomplete="new-password" />
        <flux:input wire:model="password_confirmation" :label="__('Confirm Password')" type="password" autocomplete="new-password" />

        <div class="flex items-center gap-4 pt-2">
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="updatePassword">{{ __('Save') }}</flux:button>
            <x-action-message class="text-sm text-emerald-600 dark:text-emerald-400" on="password-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>