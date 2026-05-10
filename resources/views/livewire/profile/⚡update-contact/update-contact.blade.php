<section>
    <header>
        <flux:heading size="lg">{{ __('Contact') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Your phone number and birthdate.') }}</flux:text>
    </header>

    <form wire:submit="updateContact" class="mt-6 space-y-5">
        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="phone" :label="__('Phone')" type="tel" autocomplete="tel" />
            <flux:date-picker
                wire:model="birthdate"
                :label="__('Birthdate')"
                type="input"
                selectable-header
                :min="now()->subYears(120)->toDateString()"
                :max="now()->toDateString()"
            />
        </div>

        <div class="flex items-center gap-4 pt-2">
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="updateContact">{{ __('Save') }}</flux:button>
            <x-action-message class="text-sm text-emerald-600 dark:text-emerald-400" on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>