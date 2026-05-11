<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('Prayer campaigns') }}</flux:heading>
        <flux:button :href="route('admin.prayer-campaigns.create')" variant="primary" icon="plus" wire:navigate>
            {{ __('New campaign') }}
        </flux:button>
    </div>

    @if ($this->campaigns->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
            {{ __('No campaigns yet.') }}
        </div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDir" wire:click="sort('name')">{{ __('Name') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'start_date'" :direction="$sortDir" wire:click="sort('start_date')">{{ __('Window') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'schedules_count'" :direction="$sortDir" wire:click="sort('schedules_count')">{{ __('Schedules') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'is_active'" :direction="$sortDir" wire:click="sort('is_active')">{{ __('Active') }}</flux:table.column>
                <flux:table.column align="end">&nbsp;</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->campaigns as $campaign)
                    <flux:table.row :key="'prayer-campaign-'.$campaign->id">
                        <flux:table.cell variant="strong">
                            {{ $campaign->name }}
                            <div class="text-xs text-zinc-500">{{ $campaign->slug }}</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $campaign->start_date->isoFormat('LL') }} → {{ $campaign->end_date->isoFormat('LL') }}
                        </flux:table.cell>
                        <flux:table.cell>{{ $campaign->schedules_count }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$campaign->is_active ? 'emerald' : 'zinc'">
                                {{ $campaign->is_active ? __('Active') : __('Inactive') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="inline-flex items-center gap-1">
                                <flux:tooltip :content="__('Edit')">
                                    <flux:button :href="route('admin.prayer-campaigns.edit', $campaign)" wire:navigate size="sm" variant="ghost" icon="pencil-square" />
                                </flux:tooltip>
                                <flux:tooltip :content="__('Schedules for this campaign')">
                                    <flux:button :href="route('admin.prayer-schedules.index', ['campaign' => $campaign->id])" wire:navigate size="sm" variant="ghost" icon="clock" />
                                </flux:tooltip>
                                <flux:tooltip :content="__('Delete')">
                                    <flux:modal.trigger :name="'delete-prayer-campaign-'.$campaign->id">
                                        <flux:button size="sm" variant="ghost" icon="trash" />
                                    </flux:modal.trigger>
                                </flux:tooltip>
                                <x-confirm-delete
                                    :name="'delete-prayer-campaign-'.$campaign->id"
                                    :heading="__('Delete this campaign?')"
                                    action="delete({{ $campaign->id }})"
                                />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>