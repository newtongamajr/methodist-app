<section>
    <header>
        <flux:heading size="lg">{{ __('Membership') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Your role and church affiliation.') }}</flux:text>
    </header>

    <form wire:submit="updateMembership" class="mt-6 space-y-5">
        <flux:select wire:model="nature" :label="__('I am a')">
            @foreach (\App\Enums\PersonNature::cases() as $type)
                <option value="{{ $type->value }}">{{ $type->label() }}</option>
            @endforeach
        </flux:select>

        <flux:select
            wire:model.live="region_id"
            variant="listbox"
            searchable
            clearable
            :label="__('Ecclesiastical region')"
            :placeholder="__('Pick a region…')"
        >
            @foreach ($this->regions as $region)
                <flux:select.option :value="$region->id">{{ $region->code }} — {{ $region->name }}</flux:select.option>
            @endforeach
        </flux:select>

        @if ($region_id && $this->districts->isNotEmpty())
            <flux:select
                wire:model.live="district_id"
                variant="listbox"
                searchable
                clearable
                :label="__('District')"
                :placeholder="__('Pick a district…')"
            >
                @foreach ($this->districts as $district)
                    <flux:select.option :value="$district->id">{{ $district->name }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif

        <flux:select
            wire:model.live="church_id"
            variant="listbox"
            searchable
            clearable
            :label="__('Church')"
            :placeholder="__('Search a church by name…')"
        >
            @foreach ($this->churches as $church)
                <flux:select.option :value="$church->id">
                    {{ $church->name }}@if ($church->city) — {{ $church->city }}/{{ $church->state }}@endif
                </flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex items-center gap-4 pt-2">
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="updateMembership">{{ __('Save') }}</flux:button>
            <x-action-message class="text-sm text-emerald-600 dark:text-emerald-400" on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
