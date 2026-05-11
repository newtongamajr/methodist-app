<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('Administrators') }}</flux:heading>
        <flux:button :href="route('admin.users.create')" variant="primary" icon="plus" wire:navigate>
            {{ __('New administrator') }}
        </flux:button>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search by name or email…')" icon="magnifying-glass" />

        <flux:select wire:model.live="churchFilter">
            <option value="">{{ __('All my churches') }}</option>
            @foreach ($this->churches as $church)
                <option value="{{ $church['id'] }}">{{ $church['name'] }}</option>
            @endforeach
        </flux:select>
    </div>

    @if ($this->users->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
            {{ __('No administrators yet.') }}
        </div>
    @else
        <flux:table :paginate="$this->users">
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Email') }}</flux:table.column>
                <flux:table.column>{{ __('Roles') }}</flux:table.column>
                <flux:table.column>{{ __('Churches') }}</flux:table.column>
                <flux:table.column align="end">&nbsp;</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->users as $user)
                    <flux:table.row :key="'user-'.$user->id">
                        <flux:table.cell variant="strong">
                            <a href="{{ route('admin.users.edit', $user) }}" class="hover:underline" wire:navigate>
                                {{ $user->name }}
                            </a>
                        </flux:table.cell>
                        <flux:table.cell>{{ $user->email }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-wrap gap-1">
                                @foreach ($user->roles as $role)
                                    <flux:badge wire:key="user-{{ $user->id }}-role-{{ $role->id }}" :color="$role->name === 'global_manager' ? 'rose' : 'sky'">{{ $role->name }}</flux:badge>
                                @endforeach
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-wrap gap-1">
                                @forelse ($user->churches as $c)
                                    <flux:badge wire:key="user-{{ $user->id }}-church-{{ $c->id }}" :color="$user->church_id === $c->id ? 'emerald' : 'zinc'">
                                        {{ $c->name }}
                                    </flux:badge>
                                @empty
                                    —
                                @endforelse
                            </div>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:tooltip :content="__('Delete')">
                                <flux:button wire:click="delete({{ $user->id }})" wire:confirm="{{ __('Delete this user?') }}" size="sm" variant="ghost" icon="trash" />
                            </flux:tooltip>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>