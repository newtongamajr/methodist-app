<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('Members') }}</flux:heading>
        <flux:button :href="route('admin.members.create')" variant="primary" icon="plus" wire:navigate>
            {{ __('New member') }}
        </flux:button>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search by name or email…')" icon="magnifying-glass" />

        <flux:select wire:model.live="churchFilter">
            <option value="">{{ __('All my churches') }}</option>
            @foreach ($this->churches as $church)
                <option value="{{ $church['id'] }}">{{ $church['name'] }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="natureFilter">
            <option value="">{{ __('All natures') }}</option>
            @foreach (\App\Enums\PersonNature::cases() as $type)
                <option value="{{ $type->value }}">{{ $type->label() }}</option>
            @endforeach
        </flux:select>
    </div>

    @if ($this->members->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
            {{ __('No members yet.') }}
        </div>
    @else
        <flux:table :paginate="$this->members">
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDir" wire:click="sort('name')">{{ __('Name') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'email'" :direction="$sortDir" wire:click="sort('email')">{{ __('Email') }}</flux:table.column>
                <flux:table.column>{{ __('I am a') }}</flux:table.column>
                <flux:table.column>{{ __('Churches') }}</flux:table.column>
                <flux:table.column align="end">&nbsp;</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->members as $user)
                    <flux:table.row :key="'member-'.$user->id">
                        <flux:table.cell variant="strong">{{ $user->person?->name ?? $user->name }}</flux:table.cell>
                        <flux:table.cell>{{ $user->email }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($user->person && ! empty($user->person->natures))
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($user->person->natures as $nature)
                                        <flux:badge wire:key="member-{{ $user->id }}-nat-{{ $nature }}" color="zinc">{{ \App\Enums\PersonNature::tryFrom($nature)?->label() ?? $nature }}</flux:badge>
                                    @endforeach
                                </div>
                            @else
                                —
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-wrap gap-1">
                                @forelse ($user->churches as $c)
                                    <flux:badge wire:key="member-{{ $user->id }}-church-{{ $c->id }}" :color="$user->person?->managing_church_id === $c->id ? 'emerald' : 'zinc'">
                                        {{ $c->name }}
                                    </flux:badge>
                                @empty
                                    —
                                @endforelse
                            </div>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="inline-flex items-center gap-1">
                                <flux:tooltip :content="__('Edit')">
                                    <flux:button :href="route('admin.members.edit', $user)" wire:navigate size="sm" variant="ghost" icon="pencil-square" />
                                </flux:tooltip>
                                <flux:tooltip :content="__('Delete')">
                                    <flux:button wire:click="delete({{ $user->id }})" wire:confirm="{{ __('Delete this member?') }}" size="sm" variant="ghost" icon="trash" />
                                </flux:tooltip>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>