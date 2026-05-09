<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('Fasting campaigns') }}</flux:heading>
        <flux:button :href="route('admin.fasting-campaigns.create')" variant="primary" icon="plus" wire:navigate>
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
                <flux:table.column sortable :sorted="$sortBy === 'entries_count'" :direction="$sortDir" wire:click="sort('entries_count')">{{ __('Entries') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'is_active'" :direction="$sortDir" wire:click="sort('is_active')">{{ __('Active') }}</flux:table.column>
                <flux:table.column align="end">&nbsp;</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->campaigns as $campaign)
                    <flux:table.row :key="'campaign-'.$campaign->id">
                        <flux:table.cell variant="strong">
                            {{ $campaign->name }}
                            <div class="text-xs text-zinc-500">{{ $campaign->slug }}</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $campaign->start_date->isoFormat('LL') }} → {{ $campaign->end_date->isoFormat('LL') }}
                        </flux:table.cell>
                        <flux:table.cell>{{ $campaign->entries_count }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$campaign->is_active ? 'emerald' : 'zinc'">
                                {{ $campaign->is_active ? __('Active') : __('Inactive') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="inline-flex items-center gap-1">
                                <flux:tooltip :content="__('Edit')">
                                    <flux:button :href="route('admin.fasting-campaigns.edit', $campaign)" wire:navigate size="sm" variant="ghost" icon="pencil-square" />
                                </flux:tooltip>
                                <flux:tooltip :content="__('Delete')">
                                    <flux:button wire:click="delete({{ $campaign->id }})" wire:confirm="{{ __('Delete this campaign?') }}" size="sm" variant="ghost" icon="trash" />
                                </flux:tooltip>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>