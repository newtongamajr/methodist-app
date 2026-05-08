<section>
    <header>
        <flux:heading size="lg">{{ __('Preferences') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Language and theme.') }}</flux:text>
    </header>

    <form wire:submit="updatePreferences" class="mt-6 space-y-5">
        <div class="grid gap-4 sm:grid-cols-2">
            <flux:select wire:model="locale" :label="__('Language')">
                @foreach (\App\Enums\AppLocale::cases() as $loc)
                    <option value="{{ $loc->value }}">{{ $loc->label() }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model="appearance" :label="__('Theme')">
                @foreach (\App\Enums\AppAppearance::cases() as $app)
                    <option value="{{ $app->value }}">{{ $app->label() }}</option>
                @endforeach
            </flux:select>
        </div>

        <div class="flex items-center gap-4 pt-2">
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="updatePreferences">{{ __('Save') }}</flux:button>
            <x-action-message class="text-sm text-emerald-600 dark:text-emerald-400" on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>