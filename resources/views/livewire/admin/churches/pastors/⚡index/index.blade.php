<div class="space-y-6">
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
                <flux:table.column>{{ __('Pastor') }}</flux:table.column>
                <flux:table.column>{{ __('Role') }}</flux:table.column>
                <flux:table.column>{{ __('Start') }}</flux:table.column>
                <flux:table.column>{{ __('End') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->assignments as $a)
                    <flux:table.row :key="'assign-'.$a->id">
                        <flux:table.cell variant="strong">
                            <div>{{ $a->pastor?->name }}</div>
                            <div class="text-xs text-zinc-500">{{ $a->pastor?->email }}</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="match($a->role->value) { 'main' => 'rose', 'seminarist' => 'amber', default => 'zinc' }">
                                {{ $a->role->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $a->start_date?->isoFormat('LL') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($a->end_date)
                                {{ $a->end_date->isoFormat('LL') }}
                            @else
                                <flux:badge color="emerald">{{ __('Active') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="inline-flex items-center gap-1">
                                <flux:tooltip :content="__('Edit')">
                                    <flux:button :href="route('admin.churches.pastors.edit', [$church, $a])" wire:navigate size="sm" variant="ghost" icon="pencil-square" />
                                </flux:tooltip>
                                @if (! $a->end_date)
                                    <flux:tooltip :content="__('End assignment today')">
                                        <flux:button wire:click="endAssignment({{ $a->id }})" wire:confirm="{{ __('End this assignment today?') }}" size="sm" variant="ghost" icon="x-circle" />
                                    </flux:tooltip>
                                @endif
                                <flux:tooltip :content="__('Delete')">
                                    <flux:button wire:click="delete({{ $a->id }})" wire:confirm="{{ __('Delete this assignment?') }}" size="sm" variant="ghost" icon="trash" />
                                </flux:tooltip>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>