<section>
    <header>
        <flux:heading size="lg">{{ __('Membership') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Your role and church affiliation.') }}</flux:text>
    </header>

    <form wire:submit="updateMembership" class="mt-6 space-y-5">
        <flux:select wire:model="member_type" :label="__('I am a')">
            @foreach (\App\Enums\MemberType::cases() as $type)
                <option value="{{ $type->value }}">{{ $type->label() }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="region_id" :label="__('Ecclesiastical region')">
            <option value="">{{ __('— None —') }}</option>
            @foreach ($this->regions as $region)
                <option value="{{ $region->id }}">{{ $region->code }} — {{ $region->name }}</option>
            @endforeach
        </flux:select>

        @if ($this->churches->isNotEmpty())
            <flux:select wire:model="church_id" :label="__('Church')">
                <option value="">{{ __('— None —') }}</option>
                @foreach ($this->churches as $church)
                    <option value="{{ $church->id }}">
                        {{ $church->name }}@if ($church->city) — {{ $church->city }}/{{ $church->state }}@endif
                    </option>
                @endforeach
            </flux:select>
        @endif

        <div class="flex items-center gap-4 pt-2">
            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            <x-action-message class="text-sm text-emerald-600 dark:text-emerald-400" on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>