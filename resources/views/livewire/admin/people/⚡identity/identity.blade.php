<div>
    <form wire:submit="save" class="space-y-5">
        <div class="grid gap-4 sm:grid-cols-3">
            <flux:select wire:model.live="form.person_type" :label="__('Type')" required>
                @foreach (\App\Enums\PersonType::cases() as $t)
                    <option value="{{ $t->value }}">{{ $t->label() }}</option>
                @endforeach
            </flux:select>
            <flux:input wire:model="form.name" :label="__('Name')" class="sm:col-span-2" required />
        </div>

        <flux:input wire:model="form.preferred_name" :label="__('Preferred name')" />

        <div class="grid gap-4 sm:grid-cols-3">
            <flux:select wire:model="form.tax_id_type" :label="__('Tax ID type')">
                <option value="">{{ __('— None —') }}</option>
                <option value="cpf">{{ __('CPF') }}</option>
                <option value="cnpj">{{ __('CNPJ') }}</option>
                <option value="passport">{{ __('Passport') }}</option>
                <option value="other">{{ __('Other') }}</option>
            </flux:select>
            <flux:input wire:model="form.tax_id" :label="__('Tax ID')" class="sm:col-span-2" />
        </div>

        @if ($form->person_type === 'individual')
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:date-picker wire:model="form.birthdate" :label="__('Birthdate')" />
                <flux:select wire:model="form.gender" :label="__('Gender')">
                    <option value="">{{ __('— None —') }}</option>
                    <option value="female">{{ __('Female') }}</option>
                    <option value="male">{{ __('Male') }}</option>
                    <option value="other">{{ __('Other') }}</option>
                </flux:select>
                <flux:input wire:model="form.marital_status" :label="__('Marital status')" />
            </div>
        @endif

        <flux:field>
            <flux:label>{{ __('Natures') }}</flux:label>
            <flux:description>{{ __('Pick every role this person plays — you can pick more than one (e.g. a member who is also a pastor).') }}</flux:description>
            <div class="grid gap-2 sm:grid-cols-3">
                @foreach (\App\Enums\PersonNature::cases() as $n)
                    <label wire:key="person-nat-{{ $n->value }}" class="flex items-center gap-2 rounded-md border border-zinc-200 p-2 text-sm dark:border-zinc-700">
                        <input type="checkbox" value="{{ $n->value }}" wire:model="form.natures" class="rounded-sm text-accent focus:ring-accent">
                        <span>{{ $n->label() }}</span>
                    </label>
                @endforeach
            </div>
            <flux:error name="form.natures" />
        </flux:field>

        <flux:fieldset>
            <flux:legend>{{ __('Managing church') }}</flux:legend>
            <flux:description>{{ __('The church that owns this person record. Pick a region first; the district and church narrow from there. You can also jump straight to a church and the parents back-fill.') }}</flux:description>

            <div class="mt-3 space-y-4">
                <flux:select
                    wire:model.live="region_id"
                    variant="listbox"
                    searchable
                    clearable
                    :placeholder="__('Pick a region…')"
                    :label="__('Ecclesiastical region')"
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
                    wire:model.live="form.managing_church_id"
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
            </div>
        </flux:fieldset>

        <flux:textarea wire:model="form.notes" :label="__('Notes')" rows="4" />

        <div class="flex justify-end gap-2">
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">{{ __('Save') }}</flux:button>
        </div>
    </form>
</div>
