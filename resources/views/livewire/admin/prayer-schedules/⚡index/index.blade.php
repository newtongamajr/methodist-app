<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('Prayer schedules') }}</flux:heading>
        <flux:button :href="route('admin.prayer-schedules.create')" variant="primary" icon="plus" wire:navigate>
            {{ __('New schedule') }}
        </flux:button>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <flux:select wire:model.live="churchFilter">
            <option value="">{{ __('All churches') }}</option>
            @foreach ($this->churches as $church)
                <option value="{{ $church->id }}">{{ $church->name }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="campaignFilter">
            <option value="">{{ __('All campaigns') }}</option>
            @foreach ($this->campaigns as $c)
                <option value="{{ $c->id }}">{{ $c->name }}</option>
            @endforeach
        </flux:select>
    </div>

    @if ($this->schedules->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
            {{ __('No schedules yet.') }}
        </div>
    @else
        <flux:table :paginate="$this->schedules">
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'date'" :direction="$sortDir" wire:click="sort('date')">{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('Window') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'mode'" :direction="$sortDir" wire:click="sort('mode')">{{ __('Mode') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'slots_count'" :direction="$sortDir" wire:click="sort('slots_count')">{{ __('Slots') }}</flux:table.column>
                <flux:table.column>{{ __('Campaign') }}</flux:table.column>
                <flux:table.column>{{ __('Church') }}</flux:table.column>
                <flux:table.column align="end">&nbsp;</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->schedules as $schedule)
                    <flux:table.row :key="'sch-'.$schedule->id">
                        <flux:table.cell variant="strong">{{ $schedule->date->isoFormat('LL') }}</flux:table.cell>
                        <flux:table.cell>{{ \Illuminate\Support\Str::of($schedule->start_time)->limit(5, '') }} – {{ \Illuminate\Support\Str::of($schedule->end_time)->limit(5, '') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$schedule->mode->value === 'presential' ? 'sky' : 'zinc'">
                                {{ $schedule->mode->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $schedule->slots_count }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($schedule->campaign)
                                <flux:badge color="amber">{{ $schedule->campaign->name }}</flux:badge>
                            @else
                                <span class="text-xs text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $schedule->church?->name }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="inline-flex items-center gap-1">
                                <flux:tooltip :content="__('Edit')">
                                    <flux:button :href="route('admin.prayer-schedules.edit', $schedule)" wire:navigate size="sm" variant="ghost" icon="pencil-square" />
                                </flux:tooltip>
                                <flux:tooltip :content="__('Delete')">
                                    <flux:modal.trigger :name="'delete-prayer-schedule-'.$schedule->id">
                                        <flux:button size="sm" variant="ghost" icon="trash" />
                                    </flux:modal.trigger>
                                </flux:tooltip>
                                <x-confirm-delete
                                    :name="'delete-prayer-schedule-'.$schedule->id"
                                    :heading="__('Delete this schedule?')"
                                    action="delete({{ $schedule->id }})"
                                />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>