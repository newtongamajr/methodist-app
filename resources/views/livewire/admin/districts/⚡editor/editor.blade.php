<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('admin.districts.index')" wire:navigate>{{ __('Districts') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $form->district ? __('Edit district') : __('New district') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">
            {{ $form->district ? __('Edit district') : __('New district') }}
        </flux:heading>
        <div class="flex gap-2">
            @if ($form->district?->person_id)
                <flux:tooltip :content="__('Open the full Person record (Family / Roles tabs live there)')">
                    <flux:button :href="route('admin.people.edit', $form->district->person_id)" wire:navigate icon="identification" variant="ghost" size="sm">
                        {{ __('Open as Person') }}
                    </flux:button>
                </flux:tooltip>
            @endif
            <flux:button :href="route('admin.districts.index', ['region' => $form->ecclesiastical_region_id])" variant="ghost" wire:navigate>{{ __('Back to list') }}</flux:button>
        </div>
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle" inline :heading="session('status')" />
    @endif

    @if (! $form->district)
        <form wire:submit="save" class="space-y-5">
            <flux:select
                wire:model="form.ecclesiastical_region_id"
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

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <flux:input wire:model="form.name" :label="__('Name')" required />
                </div>
                <flux:input wire:model="form.code" :label="__('Code')" maxlength="32" />
            </div>

            <flux:input wire:model="form.slug" :label="__('Slug')" :placeholder="__('Leave blank to auto-generate')" />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="form.display_order" :label="__('Display order')" type="number" min="0" />
                <flux:checkbox wire:model="form.is_active" :label="__('Active')" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:button :href="route('admin.districts.index', ['region' => $form->ecclesiastical_region_id])" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">{{ __('Save') }}</flux:button>
            </div>
        </form>
    @else
        <flux:tab.group>
            <flux:tabs wire:model.live="tab">
                <flux:tab name="details" icon="map">{{ __('Details') }}</flux:tab>
                <flux:tab name="contacts" icon="phone">{{ __('Contacts') }}</flux:tab>
                <flux:tab name="addresses" icon="map-pin">{{ __('Addresses') }}</flux:tab>
                <flux:tab name="documents" icon="document-text">{{ __('Documents') }}</flux:tab>
            </flux:tabs>

            <flux:tab.panel name="details">
                <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                    <form wire:submit="save" class="space-y-5">
                        <flux:select
                            wire:model="form.ecclesiastical_region_id"
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

                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="sm:col-span-2">
                                <flux:input wire:model="form.name" :label="__('Name')" required />
                            </div>
                            <flux:input wire:model="form.code" :label="__('Code')" maxlength="32" />
                        </div>

                        <flux:input wire:model="form.slug" :label="__('Slug')" :placeholder="__('Leave blank to auto-generate')" />

                        <div class="grid gap-4 sm:grid-cols-2">
                            <flux:input wire:model="form.display_order" :label="__('Display order')" type="number" min="0" />
                            <flux:checkbox wire:model="form.is_active" :label="__('Active')" />
                        </div>

                        <div class="flex justify-end gap-2">
                            <flux:button :href="route('admin.districts.index', ['region' => $form->ecclesiastical_region_id])" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
                            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">{{ __('Save') }}</flux:button>
                        </div>
                    </form>
                </div>
            </flux:tab.panel>

            <flux:tab.panel name="contacts">
                <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                    <livewire:admin.people.contacts :person-id="$form->district->person_id" :wire:key="'district-contacts-'.$form->district->id" />
                </div>
            </flux:tab.panel>

            <flux:tab.panel name="addresses">
                <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                    <livewire:admin.people.addresses :person-id="$form->district->person_id" :wire:key="'district-addresses-'.$form->district->id" />
                </div>
            </flux:tab.panel>

            <flux:tab.panel name="documents">
                <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                    <livewire:admin.people.documents :person-id="$form->district->person_id" :wire:key="'district-documents-'.$form->district->id" />
                </div>
            </flux:tab.panel>
        </flux:tab.group>
    @endif
</div>
