<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('admin.assignment-roles.index')" wire:navigate>{{ __('Assignment roles') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.assignment-roles.edit', $assignmentRole)" wire:navigate>{{ $assignmentRole->name }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('People') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">
            {{ __('People with the role :role', ['role' => $assignmentRole->name]) }}
        </flux:heading>
        <div class="flex gap-2">
            <flux:button :href="route('admin.assignment-roles.edit', $assignmentRole)" variant="ghost" icon="pencil-square" wire:navigate>{{ __('Edit role') }}</flux:button>
            <flux:button :href="route('admin.assignment-roles.index')" variant="ghost" wire:navigate>{{ __('Back to list') }}</flux:button>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search by person or group…')" icon="magnifying-glass" class="lg:col-span-2" />

        <flux:select wire:model.live="statusFilter" variant="listbox" clearable :placeholder="__('All assignments')">
            <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
            <flux:select.option value="ended">{{ __('Ended') }}</flux:select.option>
        </flux:select>
    </div>

    @if ($this->assignments->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
            {{ __('No people hold this role yet.') }}
        </div>
    @else
        <flux:table :paginate="$this->assignments">
            <flux:table.columns>
                <flux:table.column>{{ __('Person') }}</flux:table.column>
                <flux:table.column>{{ __('Group') }}</flux:table.column>
                <flux:table.column>{{ __('Scope') }}</flux:table.column>
                <flux:table.column>{{ __('Function') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'started_at'" :direction="$sortDir" wire:click="sort('started_at')">{{ __('Started') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'ended_at'" :direction="$sortDir" wire:click="sort('ended_at')">{{ __('Ended') }}</flux:table.column>
                <flux:table.column align="end">&nbsp;</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->assignments as $a)
                    <flux:table.row :key="'arole-asg-'.$a->id">
                        <flux:table.cell variant="strong">
                            @if ($a->person)
                                <a href="{{ route('admin.people.edit', $a->person->id) }}" wire:navigate class="text-accent hover:underline">{{ $a->person->name }}</a>
                            @else
                                —
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($a->group)
                                <a href="{{ route('admin.groups.edit', $a->group->id) }}" wire:navigate class="text-accent hover:underline">{{ $a->group->name }}</a>
                            @else
                                —
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($a->group?->church)
                                <span class="inline-flex items-center gap-1"><flux:icon.building-library class="size-4 text-zinc-400" /> {{ $a->group->church->name }}</span>
                            @elseif ($a->group?->district)
                                <span class="inline-flex items-center gap-1"><flux:icon.map class="size-4 text-zinc-400" /> {{ $a->group->district->name }}</span>
                            @elseif ($a->group?->region)
                                <span class="inline-flex items-center gap-1"><flux:icon.globe-americas class="size-4 text-zinc-400" /> {{ $a->group->region->code }}</span>
                            @elseif ($a->group)
                                <flux:badge color="rose">{{ __('National') }}</flux:badge>
                            @else
                                —
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $a->function?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $a->started_at?->isoFormat('LL') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($a->ended_at)
                                {{ $a->ended_at->isoFormat('LL') }}
                            @else
                                <flux:badge color="emerald">{{ __('Active') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            @if ($a->group)
                                <flux:tooltip :content="__('Open group')">
                                    <flux:button :href="route('admin.groups.edit', $a->group->id)" wire:navigate size="sm" variant="ghost" icon="arrow-up-right" />
                                </flux:tooltip>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>