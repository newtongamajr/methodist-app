<div>
    @if ($form->person)
        <section
            class="mb-6"
            x-data="imageCropper({ modal: 'person-photo-cropper', wireProperty: 'newPhoto', wireSave: 'savePhoto', outputName: 'photo.png' })"
            x-on:person-photo-updated.window="$wire.$refresh()"
        >
            <div class="flex items-center gap-6 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
                <div class="shrink-0">
                    @if ($this->photoUrl)
                        <img
                            src="{{ $this->photoUrl }}"
                            alt="{{ $form->person->name }}"
                            class="size-24 rounded-full object-cover ring-2 ring-zinc-200 dark:ring-zinc-700"
                        />
                    @else
                        <div class="flex size-24 items-center justify-center rounded-full bg-zinc-200 text-2xl font-semibold text-zinc-500 ring-2 ring-zinc-200 dark:bg-zinc-700 dark:text-zinc-300 dark:ring-zinc-700">
                            {{ \Illuminate\Support\Str::of($form->person->name)->substr(0, 1)->upper() }}
                        </div>
                    @endif
                </div>

                <div class="flex flex-1 flex-col gap-2">
                    <flux:heading size="sm">{{ __('Photo') }}</flux:heading>
                    <flux:text class="text-xs text-zinc-500">{{ __('Upload an image, then crop and rotate it before saving.') }}</flux:text>
                    <div class="flex flex-wrap items-center gap-3">
                        <input
                            type="file"
                            accept="image/jpeg,image/png,image/webp,image/gif"
                            class="hidden"
                            x-ref="fileInput"
                            x-on:change="pickFile($event)"
                        />
                        <flux:button
                            type="button"
                            variant="primary"
                            icon="arrow-up-tray"
                            x-on:click="$refs.fileInput.click()"
                        >
                            {{ __('Choose image') }}
                        </flux:button>
                        @if ($this->photoUrl)
                            <flux:modal.trigger name="remove-person-photo">
                                <flux:button type="button" variant="danger" icon="trash">{{ __('Remove') }}</flux:button>
                            </flux:modal.trigger>
                            <x-confirm-delete
                                name="remove-person-photo"
                                :heading="__('Remove this photo?')"
                                :confirmLabel="__('Remove')"
                                action="removePhoto"
                            />
                        @endif
                    </div>
                    @error('newPhoto')
                        <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror
                </div>
            </div>

            <flux:modal name="person-photo-cropper" class="md:w-2xl" @close="close()">
                <div class="space-y-4">
                    <div>
                        <flux:heading size="lg">{{ __('Crop the photo') }}</flux:heading>
                        <flux:text class="mt-1">{{ __('Frame and rotate the image, then save.') }}</flux:text>
                    </div>

                    <div class="relative mx-auto max-h-[60vh] overflow-hidden bg-zinc-100 dark:bg-zinc-900" wire:ignore>
                        <img data-crop-image alt="" class="block max-w-full" />
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <flux:button type="button" size="sm" icon="magnifying-glass-plus" x-on:click="zoom(0.1)">{{ __('Zoom in') }}</flux:button>
                        <flux:button type="button" size="sm" icon="magnifying-glass-minus" x-on:click="zoom(-0.1)">{{ __('Zoom out') }}</flux:button>
                        <flux:button type="button" size="sm" icon="arrow-uturn-left" x-on:click="rotate(-90)">{{ __('Rotate −90°') }}</flux:button>
                        <flux:button type="button" size="sm" icon="arrow-uturn-right" x-on:click="rotate(90)">{{ __('Rotate +90°') }}</flux:button>
                        <flux:button type="button" size="sm" icon="arrows-right-left" x-on:click="flipH()">{{ __('Flip horizontal') }}</flux:button>
                        <flux:button type="button" size="sm" icon="arrows-up-down" x-on:click="flipV()">{{ __('Flip vertical') }}</flux:button>
                    </div>

                    <div class="flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                        </flux:modal.close>
                        <flux:button type="button" variant="primary" icon="check" x-on:click="save()" x-bind:disabled="saving">
                            <span x-show="! saving">{{ __('Save photo') }}</span>
                            <span x-show="saving" x-cloak>{{ __('Saving…') }}</span>
                        </flux:button>
                    </div>
                </div>
            </flux:modal>
        </section>
    @endif

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
            <flux:select wire:model.live="form.tax_id_type" :label="__('Tax ID type')">
                <option value="">{{ __('— None —') }}</option>
                <option value="cpf">{{ __('CPF') }}</option>
                <option value="cnpj">{{ __('CNPJ') }}</option>
                <option value="passport">{{ __('Passport') }}</option>
                <option value="other">{{ __('Other') }}</option>
            </flux:select>
            @php
                // Drive the live mask off the selected tax_id_type. Null entries are
                // skipped by Blade's `:`-binding so the input has no x-mask /
                // maxlength when the type is passport or other.
                $taxIdMask = match ($form->tax_id_type) {
                    'cpf' => '999.999.999-99',
                    'cnpj' => '99.999.999/9999-99',
                    default => null,
                };
                $taxIdPlaceholder = match ($form->tax_id_type) {
                    'cpf' => '000.000.000-00',
                    'cnpj' => '00.000.000/0000-00',
                    default => null,
                };
                $taxIdMaxLength = match ($form->tax_id_type) {
                    'cpf' => 14,
                    'cnpj' => 18,
                    default => null,
                };
            @endphp
            <div class="sm:col-span-2">
                <flux:input
                    wire:model="form.tax_id"
                    :label="__('Tax ID')"
                    :inputmode="$taxIdMask ? 'numeric' : null"
                    :maxlength="$taxIdMaxLength"
                    :x-mask="$taxIdMask"
                    :placeholder="$taxIdPlaceholder"
                />
            </div>
        </div>

        @if ($form->person_type === 'individual')
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:date-picker
                    wire:model="form.birthdate"
                    :label="__('Birthdate')"
                    type="input"
                    selectable-header
                    :min="now()->subYears(120)->toDateString()"
                    :max="now()->toDateString()"
                />
                <flux:select wire:model="form.gender" :label="__('Gender')">
                    <option value="">{{ __('— None —') }}</option>
                    @foreach (\App\Enums\Gender::cases() as $g)
                        <option value="{{ $g->value }}">{{ $g->label() }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="form.marital_status" :label="__('Marital status')">
                    <option value="">{{ __('— None —') }}</option>
                    @foreach (\App\Enums\MaritalStatus::cases() as $ms)
                        <option value="{{ $ms->value }}">{{ $ms->label() }}</option>
                    @endforeach
                </flux:select>
            </div>
        @else
            <flux:date-picker
                wire:model="form.birthdate"
                :label="__('Foundation date')"
                type="input"
                selectable-header
                :max="now()->toDateString()"
            />
        @endif

        <flux:field>
            <flux:label>{{ __('Natures') }}</flux:label>
            <flux:description>
                {{ $form->person_type === 'organization'
                    ? __('Pick the organizational role this record represents.')
                    : __('Pick every role this person plays — you can pick more than one (e.g. a member who is also a pastor).') }}
            </flux:description>
            <div class="grid gap-2 sm:grid-cols-3">
                @foreach (\App\Enums\PersonNature::optionsForPersonType($form->person_type) as $value => $label)
                    <label wire:key="person-nat-{{ $value }}" class="flex items-center gap-2 rounded-md border border-zinc-200 p-2 text-sm dark:border-zinc-700">
                        <input type="checkbox" value="{{ $value }}" wire:model="form.natures" class="rounded-sm text-accent focus:ring-accent">
                        <span>{{ $label }}</span>
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
