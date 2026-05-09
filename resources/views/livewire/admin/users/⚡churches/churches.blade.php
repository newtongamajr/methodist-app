<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('admin.users.index')" wire:navigate>{{ __('Administrators') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.users.edit', $user)" wire:navigate>{{ $user->name }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('Churches') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Manage churches') }}</flux:heading>
            <flux:text class="mt-1">{{ $user->name }} · {{ $user->email }}</flux:text>
        </div>
        <flux:button :href="route('admin.users.edit', $user)" variant="ghost" wire:navigate>{{ __('Back to user') }}</flux:button>
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle" inline :heading="session('status')" />
    @endif

    {{-- Add church section: searchable listbox + Add button. Excludes already-attached. --}}
    <section class="space-y-3 rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="md">{{ __('Add a church') }}</flux:heading>
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-64">
                <flux:select
                    wire:model.live="selectedChurchId"
                    variant="listbox"
                    searchable
                    clearable
                    :placeholder="$this->selectableChurches->isEmpty() ? __('No more churches available to assign.') : __('Search a church by name…')"
                    :disabled="$this->selectableChurches->isEmpty()"
                >
                    @foreach ($this->selectableChurches as $church)
                        <flux:select.option :value="$church['id']">
                            {{ $church['name'] }}@if ($church['city']) — {{ $church['city'] }}/{{ $church['state'] }}@endif
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <flux:button
                wire:click="attach"
                variant="primary"
                icon="plus"
                :disabled="! $selectedChurchId"
            >
                {{ __('Add church') }}
            </flux:button>
        </div>
    </section>

    {{-- Currently attached churches: each row has a primary toggle + remove. --}}
    <section class="space-y-3">
        <flux:heading size="md">{{ __('Attached churches') }}</flux:heading>

        @if ($this->attachments->isEmpty())
            <div class="rounded-lg border border-zinc-200 bg-white p-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
                {{ __('No churches assigned to this user yet.') }}
            </div>
        @else
            <ul class="space-y-2">
                @foreach ($this->attachments as $church)
                    <li
                        wire:key="attached-{{ $church->id }}"
                        class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900"
                    >
                        <div class="flex-1 min-w-48">
                            <div class="font-medium">{{ $church->name }}</div>
                            @if ($church->city)
                                <div class="text-xs text-zinc-500">{{ $church->city }}/{{ $church->state }}</div>
                            @endif
                        </div>

                        <div class="flex items-center gap-3">
                            @if ($church->pivot->is_primary)
                                <flux:badge color="emerald">{{ __('Primary') }}</flux:badge>
                            @else
                                <flux:button
                                    wire:click="setPrimary({{ $church->id }})"
                                    size="sm"
                                    variant="ghost"
                                    icon="star"
                                >
                                    {{ __('Set as primary') }}
                                </flux:button>
                            @endif

                            <flux:tooltip :content="__('Remove this church from the user')">
                                <flux:button
                                    wire:click="detach({{ $church->id }})"
                                    wire:confirm="{{ __('Remove this church from the user?') }}"
                                    size="sm"
                                    variant="ghost"
                                    icon="x-mark"
                                />
                            </flux:tooltip>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
