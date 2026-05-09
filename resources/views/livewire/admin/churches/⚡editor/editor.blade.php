<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('admin.churches.index')" wire:navigate>{{ __('Churches') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $form->church ? __('Edit church') : __('New church') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">
            {{ $form->church ? __('Edit church') : __('New church') }}
        </flux:heading>
        <div class="flex gap-2">
            @if ($form->church?->person_id)
                <flux:tooltip :content="__('Open the full Person record (Family / Roles tabs live there)')">
                    <flux:button :href="route('admin.people.edit', $form->church->person_id)" wire:navigate icon="identification" variant="ghost" size="sm">
                        {{ __('Open as Person') }}
                    </flux:button>
                </flux:tooltip>
            @endif
            <flux:button :href="route('admin.churches.index')" variant="ghost" wire:navigate>{{ __('Back to list') }}</flux:button>
        </div>
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle" inline :heading="session('status')" />
    @endif

    @if (! $form->church)
        {{-- New church: just the form (incl. cached address/contact and master-user blocks). --}}
        <form wire:submit="save" class="space-y-6">
            <section class="space-y-4">
                <flux:heading size="lg">{{ __('Church') }}</flux:heading>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:select
                        wire:model.live="form.ecclesiastical_region_id"
                        variant="listbox"
                        searchable
                        clearable
                        :label="__('Ecclesiastical region')"
                        :placeholder="__('Pick a region…')"
                        required
                    >
                        @foreach ($this->regions as $region)
                            <flux:select.option :value="$region->id">{{ $region->code }} — {{ $region->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="form.type" :label="__('Type')" required>
                        @foreach (\App\Enums\ChurchType::cases() as $t)
                            <option value="{{ $t->value }}">{{ $t->label() }}</option>
                        @endforeach
                    </flux:select>
                </div>

                @if ($form->ecclesiastical_region_id)
                    <flux:select
                        wire:model="form.district_id"
                        variant="listbox"
                        searchable
                        clearable
                        :label="__('District')"
                        :placeholder="$this->districts->isEmpty() ? __('No districts in this region yet.') : __('Pick a district…')"
                        :disabled="$this->districts->isEmpty()"
                        :required="$form->districtIsRequired()"
                    >
                        @foreach ($this->districts as $district)
                            <flux:select.option :value="$district->id">{{ $district->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                <flux:input wire:model="form.name" :label="__('Name')" required />

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="form.code" :label="__('Code')" maxlength="32" />
                    <flux:input wire:model="form.slug" :label="__('Slug')" :placeholder="__('Leave blank to auto-generate')" />
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:input wire:model="form.timezone" :label="__('Timezone')" />
                    <flux:input wire:model="form.max_prayers_per_slot" :label="__('Max prayers per slot')" type="number" min="1" max="200" required />
                    <flux:select wire:model="form.default_mode" :label="__('Default mode')">
                        @foreach (\App\Enums\LocationMode::cases() as $m)
                            <option value="{{ $m->value }}">{{ $m->label() }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:input wire:model="form.address" :label="__('Address')" />

                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:input wire:model="form.city" :label="__('City')" />
                    <flux:input wire:model="form.state" :label="__('State (UF)')" maxlength="2" />
                    <flux:input wire:model="form.zip" :label="__('ZIP')" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="form.phone" :label="__('Phone')" type="tel" />
                    <flux:input wire:model="form.email" :label="__('Email')" type="email" />
                </div>

                <flux:checkbox wire:model="form.is_active" :label="__('Active')" />
            </section>

            <section class="space-y-4 rounded-lg border border-amber-200 bg-amber-50/40 p-5 dark:border-amber-700 dark:bg-amber-900/20">
                <div>
                    <flux:heading size="lg">{{ __('Master user') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('A local manager account that will administer this church and create more admins for it.') }}</flux:text>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="form.master_name" :label="__('Name')" required />
                    <flux:input wire:model="form.master_email" :label="__('Email')" type="email" required />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="form.master_phone" :label="__('Phone')" type="tel" />
                    <flux:input wire:model="form.master_password" :label="__('Initial password')" type="password" required />
                </div>
            </section>

            <div class="flex justify-end gap-2">
                <flux:button :href="route('admin.churches.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">{{ __('Save') }}</flux:button>
            </div>
        </form>
    @else
        <flux:tab.group>
            <flux:tabs wire:model.live="tab">
                <flux:tab name="details" icon="building-library">{{ __('Details') }}</flux:tab>
                <flux:tab name="contacts" icon="phone">{{ __('Contacts') }}</flux:tab>
                <flux:tab name="addresses" icon="map-pin">{{ __('Addresses') }}</flux:tab>
                <flux:tab name="documents" icon="document-text">{{ __('Documents') }}</flux:tab>
                <flux:tab name="administrators" icon="users">{{ __('Administrators') }}</flux:tab>
            </flux:tabs>

            <flux:tab.panel name="details">
                <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                    <form wire:submit="save" class="space-y-6">
                        <section class="space-y-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <flux:select
                                    wire:model.live="form.ecclesiastical_region_id"
                                    variant="listbox"
                                    searchable
                                    clearable
                                    :label="__('Ecclesiastical region')"
                                    :placeholder="__('Pick a region…')"
                                    required
                                >
                                    @foreach ($this->regions as $region)
                                        <flux:select.option :value="$region->id">{{ $region->code }} — {{ $region->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:select wire:model="form.type" :label="__('Type')" required>
                                    @foreach (\App\Enums\ChurchType::cases() as $t)
                                        <option value="{{ $t->value }}">{{ $t->label() }}</option>
                                    @endforeach
                                </flux:select>
                            </div>

                            @if ($form->ecclesiastical_region_id)
                                <flux:select
                                    wire:model="form.district_id"
                                    variant="listbox"
                                    searchable
                                    clearable
                                    :label="__('District')"
                                    :placeholder="$this->districts->isEmpty() ? __('No districts in this region yet.') : __('Pick a district…')"
                                    :disabled="$this->districts->isEmpty()"
                                    :required="$form->districtIsRequired()"
                                >
                                    @foreach ($this->districts as $district)
                                        <flux:select.option :value="$district->id">{{ $district->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            @endif

                            <flux:input wire:model="form.name" :label="__('Name')" required />

                            <div class="grid gap-4 sm:grid-cols-2">
                                <flux:input wire:model="form.code" :label="__('Code')" maxlength="32" />
                                <flux:input wire:model="form.slug" :label="__('Slug')" :placeholder="__('Leave blank to auto-generate')" />
                            </div>

                            <div class="grid gap-4 sm:grid-cols-3">
                                <flux:input wire:model="form.timezone" :label="__('Timezone')" />
                                <flux:input wire:model="form.max_prayers_per_slot" :label="__('Max prayers per slot')" type="number" min="1" max="200" required />
                                <flux:select wire:model="form.default_mode" :label="__('Default mode')">
                                    @foreach (\App\Enums\LocationMode::cases() as $m)
                                        <option value="{{ $m->value }}">{{ $m->label() }}</option>
                                    @endforeach
                                </flux:select>
                            </div>

                            <flux:callout variant="info" icon="information-circle" inline :heading="__('Address, phone, email and CNPJ live on the Contacts and Addresses tabs (Person is the source of truth).')" />

                            <flux:checkbox wire:model="form.is_active" :label="__('Active')" />
                        </section>

                        <div class="flex justify-end gap-2">
                            <flux:button :href="route('admin.churches.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
                            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">{{ __('Save') }}</flux:button>
                        </div>
                    </form>
                </div>
            </flux:tab.panel>

            <flux:tab.panel name="contacts">
                <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                    <livewire:admin.people.contacts :person-id="$form->church->person_id" :wire:key="'church-contacts-'.$form->church->id" />
                </div>
            </flux:tab.panel>

            <flux:tab.panel name="addresses">
                <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                    <livewire:admin.people.addresses :person-id="$form->church->person_id" :wire:key="'church-addresses-'.$form->church->id" />
                </div>
            </flux:tab.panel>

            <flux:tab.panel name="documents">
                <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                    <livewire:admin.people.documents :person-id="$form->church->person_id" :wire:key="'church-documents-'.$form->church->id" />
                </div>
            </flux:tab.panel>

            <flux:tab.panel name="administrators">
                <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                    <flux:heading size="lg">{{ __('Administrators of this church') }}</flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-500">
                        {{ __('Use the Users page to manage church administrators.') }}
                    </flux:text>
                    <div class="mt-3">
                        <flux:button :href="route('admin.users.index', ['church' => $form->church->id])" wire:navigate icon="users">
                            {{ __('Open users') }}
                        </flux:button>
                    </div>
                </div>
            </flux:tab.panel>
        </flux:tab.group>
    @endif
</div>
