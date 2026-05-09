<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('admin.churches.index')" wire:navigate>{{ __('Churches') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.churches.edit', $church)" wire:navigate>{{ $church->name }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('Pastors') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Pastors') }}</flux:heading>
            <flux:text class="mt-1">{{ $church->name }}</flux:text>
        </div>
        <div class="flex gap-2">
            <flux:button :href="route('admin.churches.index')" variant="ghost" wire:navigate>{{ __('Back to churches') }}</flux:button>
            <flux:button :href="route('admin.churches.pastors.create', $church)" variant="primary" icon="plus" wire:navigate>
                {{ __('New assignment') }}
            </flux:button>
        </div>
    </div>

    <flux:tab.group>
        <flux:tabs wire:model.live="filter">
            <flux:tab name="current">{{ __('Current') }}</flux:tab>
            <flux:tab name="past">{{ __('Past') }}</flux:tab>
            <flux:tab name="future">{{ __('Upcoming') }}</flux:tab>
            <flux:tab name="all">{{ __('All') }}</flux:tab>
        </flux:tabs>
    </flux:tab.group>

    @if ($this->assignments->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
            {{ __('No assignments in this view.') }}
        </div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'person'" :direction="$sortDir" wire:click="sort('person')">{{ __('Pastor') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'function'" :direction="$sortDir" wire:click="sort('function')">{{ __('Function') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'started_at'" :direction="$sortDir" wire:click="sort('started_at')">{{ __('Start') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'ended_at'" :direction="$sortDir" wire:click="sort('ended_at')">{{ __('End') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->assignments as $a)
                    <flux:table.row :key="'assign-'.$a->id">
                        <flux:table.cell variant="strong">
                            <div>{{ $a->person?->name }}</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="match($a->function?->slug) { 'main_pastor' => 'rose', 'seminarist' => 'amber', default => 'zinc' }">
                                {{ $a->function?->name }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $a->started_at?->isoFormat('LL') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($a->ended_at)
                                {{ $a->ended_at->isoFormat('LL') }}
                            @else
                                <flux:badge color="emerald">{{ __('Active') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="inline-flex items-center gap-1">
                                <flux:tooltip :content="__('Edit')">
                                    <flux:button :href="route('admin.churches.pastors.edit', [$church, $a])" wire:navigate size="sm" variant="ghost" icon="pencil-square" />
                                </flux:tooltip>
                                @if (! $a->ended_at)
                                    <flux:tooltip :content="__('End assignment today')">
                                        <flux:modal.trigger :name="'end-pastor-assignment-'.$a->id">
                                            <flux:button size="sm" variant="ghost" icon="x-circle" />
                                        </flux:modal.trigger>
                                    </flux:tooltip>
                                    <x-confirm-delete
                                        :name="'end-pastor-assignment-'.$a->id"
                                        :heading="__('End this assignment today?')"
                                        :confirmLabel="__('End')"
                                        action="endAssignment({{ $a->id }})"
                                    />
                                @endif
                                <flux:tooltip :content="__('Delete')">
                                    <flux:modal.trigger :name="'delete-pastor-assignment-'.$a->id">
                                        <flux:button size="sm" variant="ghost" icon="trash" />
                                    </flux:modal.trigger>
                                </flux:tooltip>
                                <x-confirm-delete
                                    :name="'delete-pastor-assignment-'.$a->id"
                                    :heading="__('Delete this assignment?')"
                                    action="delete({{ $a->id }})"
                                />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>