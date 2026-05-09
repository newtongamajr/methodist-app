<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('admin.regions.index')" wire:navigate>{{ __('Ecclesiastical regions') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $form->region ? __('Edit region') : __('New region') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">
            {{ $form->region ? __('Edit region') : __('New region') }}
        </flux:heading>
        <div class="flex gap-2">
            @if ($form->region?->person_id)
                <flux:tooltip :content="__('Edit identity, contacts, addresses, documents on the linked Person record')">
                    <flux:button :href="route('admin.people.edit', $form->region->person_id)" wire:navigate icon="identification">
                        {{ __('Open as Person') }}
                    </flux:button>
                </flux:tooltip>
            @endif
            <flux:button :href="route('admin.regions.index')" variant="ghost" wire:navigate>{{ __('Back to list') }}</flux:button>
        </div>
    </div>

    <form wire:submit="save" class="space-y-5">
        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="form.code" :label="__('Code')" required maxlength="16" />
            <flux:input wire:model="form.display_order" :label="__('Display order')" type="number" min="0" />
        </div>

        <flux:input wire:model="form.name" :label="__('Name')" required />

        <flux:select wire:model="form.kind" :label="__('Kind')" required>
            @foreach (\App\Enums\RegionKind::cases() as $k)
                <option value="{{ $k->value }}">{{ $k->label() }}</option>
            @endforeach
        </flux:select>

        <div class="flex justify-end gap-2">
            <flux:button :href="route('admin.regions.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">{{ __('Save') }}</flux:button>
        </div>
    </form>
</div>