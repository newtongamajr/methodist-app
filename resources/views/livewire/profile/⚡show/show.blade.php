<div class="mx-auto max-w-3xl space-y-6 px-4 py-12 sm:px-6 lg:px-8">
    <flux:heading size="xl">{{ __('Profile') }}</flux:heading>

    <flux:tab.group>
        <flux:tabs wire:model.live="tab">
            <flux:tab name="identity">{{ __('Identity') }}</flux:tab>
            <flux:tab name="avatar">{{ __('Avatar') }}</flux:tab>
            <flux:tab name="membership">{{ __('Membership') }}</flux:tab>
            <flux:tab name="contact">{{ __('Contact') }}</flux:tab>
            <flux:tab name="family">{{ __('Family') }}</flux:tab>
            <flux:tab name="preferences">{{ __('Preferences') }}</flux:tab>
            <flux:tab name="password">{{ __('Password') }}</flux:tab>
            <flux:tab name="danger">{{ __('Danger zone') }}</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="identity">
            <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                <livewire:profile.update-identity />
            </div>
        </flux:tab.panel>

        <flux:tab.panel name="avatar">
            <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                <livewire:profile.update-avatar />
            </div>
        </flux:tab.panel>

        <flux:tab.panel name="membership">
            <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                <livewire:profile.update-membership />
            </div>
        </flux:tab.panel>

        <flux:tab.panel name="contact">
            <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                <livewire:profile.update-contact />
            </div>
        </flux:tab.panel>

        <flux:tab.panel name="family">
            <div class="rounded-lg bg-white p-6 shadow-xs dark:bg-zinc-800 sm:p-8">
                <livewire:profile.family />
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
