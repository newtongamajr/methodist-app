<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('admin.people.index')" wire:navigate>{{ __('People') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $person ? __('Edit person') : __('New person') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">
            {{ $person ? $person->name : __('New person') }}
        </flux:heading>
        <flux:button :href="route('admin.people.index')" variant="ghost" wire:navigate>{{ __('Back to list') }}</flux:button>
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle" inline :heading="session('status')" />
    @endif

    @if (! $person)
        {{-- New person: only the Identity tab is meaningful until the row exists. --}}
        <livewire:admin.people.identity :person-id="null" />
    @else
        <flux:tab.group>
            <flux:tabs wire:model.live="tab">
                <flux:tab name="identity" icon="identification">{{ __('Identity') }}</flux:tab>
                <flux:tab name="contacts" icon="phone">{{ __('Contacts') }}</flux:tab>
                <flux:tab name="addresses" icon="map-pin">{{ __('Addresses') }}</flux:tab>
                <flux:tab name="documents" icon="document-text">{{ __('Documents') }}</flux:tab>
            </flux:tabs>

            <flux:tab.panel name="identity">
                <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                    <livewire:admin.people.identity :person-id="$person->id" :wire:key="'identity-'.$person->id" />
                </div>
            </flux:tab.panel>

            <flux:tab.panel name="contacts">
                <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                    <livewire:admin.people.contacts :person-id="$person->id" :wire:key="'contacts-'.$person->id" />
                </div>
            </flux:tab.panel>

            <flux:tab.panel name="addresses">
                <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                    <livewire:admin.people.addresses :person-id="$person->id" :wire:key="'addresses-'.$person->id" />
                </div>
            </flux:tab.panel>

            <flux:tab.panel name="documents">
                <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                    <livewire:admin.people.documents :person-id="$person->id" :wire:key="'documents-'.$person->id" />
                </div>
            </flux:tab.panel>
        </flux:tab.group>
    @endif
</div>
