<div>
    <flux:heading size="xl" class="text-center">{{ __('Create your account') }}</flux:heading>
    <flux:text class="mt-2 text-center">{{ __('Join the campaign and pray with us.') }}</flux:text>

    <form wire:submit="register" class="mt-8 space-y-5">
        <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

        <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="username" />

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="password" :label="__('Password')" type="password" required autocomplete="new-password" />
            <flux:input wire:model="password_confirmation" :label="__('Confirm Password')" type="password" required autocomplete="new-password" />
        </div>

        <flux:select wire:model="nature" :label="__('I am a')">
            @foreach (\App\Enums\PersonNature::individualOptions() as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
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

        @if ($region_id)
            <flux:select
                wire:model.live="district_id"
                variant="listbox"
                searchable
                clearable
                :label="__('District')"
                :placeholder="$this->districts->isEmpty() ? __('No districts in this region yet.') : __('Pick a district…')"
                :disabled="$this->districts->isEmpty()"
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

        <div
            class="grid gap-4 sm:grid-cols-6"
            x-data="{
                masks: @js(collect(\App\Enums\Country::cases())->mapWithKeys(fn ($c) => [$c->value => $c->mobileMask()])->all()),
                phoneMask() { return this.masks[$wire.phone_country] ?? ''; },
            }"
        >
            <div class="sm:col-span-2">
                <flux:select
                    wire:model.live="phone_country"
                    variant="listbox"
                    searchable
                    :label="__('Country')"
                    :placeholder="__('Pick a country…')"
                >
                    @foreach (\App\Enums\Country::options() as $iso => $name)
                        <flux:select.option :value="$iso">{{ $name }} (+{{ \App\Enums\Country::from($iso)->phoneCode() }})</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="sm:col-span-2">
                <flux:input
                    wire:model="phone"
                    :label="__('Mobile')"
                    type="tel"
                    autocomplete="tel"
                    x-mask:dynamic="phoneMask()"
                    x-bind:placeholder="phoneMask()"
                />
            </div>
            <div class="sm:col-span-2">
                <flux:date-picker
                    wire:model="birthdate"
                    :label="__('Birthdate')"
                    type="input"
                    selectable-header
                    :min="now()->subYears(120)->toDateString()"
                    :max="now()->toDateString()"
                />
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <flux:select wire:model="gender" :label="__('Gender')">
                <option value="">{{ __('— None —') }}</option>
                @foreach (\App\Enums\Gender::cases() as $g)
                    <option value="{{ $g->value }}">{{ $g->label() }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model="marital_status" :label="__('Marital status')">
                <option value="">{{ __('— None —') }}</option>
                @foreach (\App\Enums\MaritalStatus::cases() as $ms)
                    <option value="{{ $ms->value }}">{{ $ms->label() }}</option>
                @endforeach
            </flux:select>
            <flux:input
                wire:model="tax_id"
                :label="__('CPF')"
                inputmode="numeric"
                maxlength="14"
                x-mask="999.999.999-99"
                placeholder="000.000.000-00"
            />
        </div>

        <flux:select wire:model="locale" :label="__('Language')">
            @foreach (\App\Enums\AppLocale::cases() as $loc)
                <option value="{{ $loc->value }}">{{ $loc->label() }}</option>
            @endforeach
        </flux:select>

        <div class="flex items-center justify-between gap-4 pt-2">
            <a href="{{ route('login') }}" class="text-sm font-medium text-accent hover:underline dark:text-rose-300" wire:navigate>
                {{ __('Already registered?') }}
            </a>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="register">
                {{ __('Register') }}
            </flux:button>
        </div>
    </form>
</div>