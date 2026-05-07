<x-app-layout>
    <x-slot name="header">
        <flux:heading size="lg">{{ __('Profile') }}</flux:heading>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-zinc-800 sm:p-8">
                <livewire:profile.update-avatar />
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-zinc-800 sm:p-8">
                <livewire:profile.update-identity />
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-zinc-800 sm:p-8">
                <livewire:profile.update-membership />
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-zinc-800 sm:p-8">
                <livewire:profile.update-contact />
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-zinc-800 sm:p-8">
                <livewire:profile.update-preferences />
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-zinc-800 sm:p-8">
                <livewire:profile.update-password-form />
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-zinc-800 sm:p-8">
                <livewire:profile.delete-user-form />
            </div>
        </div>
    </div>
</x-app-layout>