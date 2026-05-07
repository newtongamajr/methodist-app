<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">
            {{ $region ? __('Edit region') : __('New region') }}
        </flux:heading>
        <flux:button :href="route('admin.regions.index')" variant="ghost" wire:navigate>{{ __('Back to list') }}</flux:button>
    </div>

    <form wire:submit="save" class="space-y-5">
        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="code" :label="__('Code')" required maxlength="16" />
            <flux:input wire:model="display_order" :label="__('Display order')" type="number" min="0" />
        </div>

        <flux:input wire:model="name" :label="__('Name')" required />

        <flux:select wire:model="kind" :label="__('Kind')" required>
            @foreach (\App\Enums\RegionKind::cases() as $k)
                <option value="{{ $k->value }}">{{ $k->label() }}</option>
            @endforeach
        </flux:select>

        <div class="flex justify-end gap-2">
            <flux:button :href="route('admin.regions.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
        </div>
    </form>
</div>