<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('Prayer campaigns') }}</flux:heading>
        <flux:button :href="route('admin.prayer-campaigns.create')" variant="primary" icon="plus" wire:navigate>
            {{ __('New campaign') }}
        </flux:button>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700 text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('Name') }}</th>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('Window') }}</th>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('Schedules') }}</th>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('Active') }}</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($this->campaigns as $campaign)
                    <tr wire:key="prayer-campaign-{{ $campaign->id }}">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.prayer-campaigns.edit', $campaign) }}" class="font-medium text-[#c8202f] hover:underline dark:text-rose-300" wire:navigate>
                                {{ $campaign->name }}
                            </a>
                            <div class="text-xs text-zinc-500">{{ $campaign->slug }}</div>
                        </td>
                        <td class="px-4 py-3">
                            {{ $campaign->start_date->isoFormat('LL') }} → {{ $campaign->end_date->isoFormat('LL') }}
                        </td>
                        <td class="px-4 py-3">{{ $campaign->schedules_count }}</td>
                        <td class="px-4 py-3">
                            <flux:badge :color="$campaign->is_active ? 'emerald' : 'zinc'">
                                {{ $campaign->is_active ? __('Active') : __('Inactive') }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-end">
                            <flux:button wire:click="delete({{ $campaign->id }})" wire:confirm="{{ __('Delete this campaign?') }}" size="sm" variant="ghost" icon="trash" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-zinc-500">{{ __('No campaigns yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>