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

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700 text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('Pastor') }}</th>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('Role') }}</th>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('Start') }}</th>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('End') }}</th>
                    <th class="px-4 py-2 text-end font-semibold">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($this->assignments as $a)
                    <tr wire:key="assign-{{ $a->id }}">
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $a->pastor?->name }}</div>
                            <div class="text-xs text-zinc-500">{{ $a->pastor?->email }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge :color="match($a->role->value) { 'main' => 'rose', 'seminarist' => 'amber', default => 'zinc' }">
                                {{ $a->role->label() }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3">{{ $a->start_date?->isoFormat('LL') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($a->end_date)
                                {{ $a->end_date->isoFormat('LL') }}
                            @else
                                <flux:badge color="emerald">{{ __('Active') }}</flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="inline-flex items-center gap-1">
                                <flux:button :href="route('admin.churches.pastors.edit', [$church, $a])" wire:navigate size="sm" variant="ghost" icon="pencil-square" />
                                @if (! $a->end_date)
                                    <flux:tooltip :content="__('End assignment today')">
                                        <flux:button wire:click="endAssignment({{ $a->id }})" wire:confirm="{{ __('End this assignment today?') }}" size="sm" variant="ghost" icon="x-circle" />
                                    </flux:tooltip>
                                @endif
                                <flux:button wire:click="delete({{ $a->id }})" wire:confirm="{{ __('Delete this assignment?') }}" size="sm" variant="ghost" icon="trash" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-zinc-500">{{ __('No assignments in this view.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>