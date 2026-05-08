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
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Window') }}</flux:table.column>
                <flux:table.column>{{ __('Schedules') }}</flux:table.column>
                <flux:table.column>{{ __('Active') }}</flux:table.column>
                <flux:table.column align="end">&nbsp;</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->campaigns as $campaign)
                    <flux:table.row :key="'prayer-campaign-'.$campaign->id">
                        <flux:table.cell variant="strong">
                            <a href="{{ route('admin.prayer-campaigns.edit', $campaign) }}" class="hover:underline" wire:navigate>
                                {{ $campaign->name }}
                            </a>
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
                            <flux:tooltip :content="__('Delete')">
                                <flux:button wire:click="delete({{ $campaign->id }})" wire:confirm="{{ __('Delete this campaign?') }}" size="sm" variant="ghost" icon="trash" />
                            </flux:tooltip>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>