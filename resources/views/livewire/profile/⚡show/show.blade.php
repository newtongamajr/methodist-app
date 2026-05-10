<div class="mx-auto max-w-5xl space-y-6 px-4 py-12 sm:px-6 lg:px-8">
    <flux:heading size="xl">{{ __('Profile') }}</flux:heading>

    <flux:tab.group>
        <flux:tabs wire:model.live="tab">
            <flux:tab name="identity" icon="identification">{{ __('Identity') }}</flux:tab>
            <flux:tab name="avatar" icon="user-circle">{{ __('Avatar') }}</flux:tab>
            <flux:tab name="contacts" icon="phone">{{ __('Contacts') }}</flux:tab>
            <flux:tab name="addresses" icon="map-pin">{{ __('Addresses') }}</flux:tab>
            <flux:tab name="documents" icon="document-text">{{ __('Documents') }}</flux:tab>
            <flux:tab name="family" icon="users">{{ __('Family') }}</flux:tab>
            <flux:tab name="membership" icon="building-library">{{ __('Membership') }}</flux:tab>
            <flux:tab name="preferences" icon="cog-6-tooth">{{ __('Preferences') }}</flux:tab>
            <flux:tab name="password" icon="key">{{ __('Password') }}</flux:tab>
            <flux:tab name="danger" icon="exclamation-triangle">{{ __('Danger zone') }}</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="identity">
            <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                <livewire:profile.update-identity />
                @if ($personId)
                    <hr class="my-6 border-zinc-200 dark:border-zinc-700" />
                    <livewire:admin.people.identity :person-id="$personId" :wire:key="'profile-identity-'.$personId" />
                @endif
            </div>
        </flux:tab.panel>

        <flux:tab.panel name="avatar">
            <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                <livewire:profile.update-avatar />
            </div>
        </flux:tab.panel>

        @if ($personId)
            <flux:tab.panel name="contacts">
                <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                    <livewire:admin.people.contacts :person-id="$personId" :wire:key="'profile-contacts-'.$personId" />
                </div>
            </flux:tab.panel>

            <flux:tab.panel name="addresses">
                <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                    <livewire:admin.people.addresses :person-id="$personId" :wire:key="'profile-addresses-'.$personId" />
                </div>
            </flux:tab.panel>

            <flux:tab.panel name="documents">
                <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                    <livewire:admin.people.documents :person-id="$personId" :wire:key="'profile-documents-'.$personId" />
                </div>
            </flux:tab.panel>

            <flux:tab.panel name="family">
                <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                    <livewire:admin.people.family :person-id="$personId" :wire:key="'profile-family-'.$personId" />
                </div>
            </flux:tab.panel>
        @endif

        <flux:tab.panel name="membership">
            <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                <livewire:profile.update-membership />
            </div>
        </flux:tab.panel>

        <flux:tab.panel name="preferences">
            <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                <livewire:profile.update-preferences />
            </div>
        </flux:tab.panel>

        <flux:tab.panel name="password">
            <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                <livewire:profile.update-password-form />
            </div>
        </flux:tab.panel>

        <flux:tab.panel name="danger">
            <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                <livewire:profile.delete-user-form />
            </div>
        </flux:tab.panel>
    </flux:tab.group>
</div>