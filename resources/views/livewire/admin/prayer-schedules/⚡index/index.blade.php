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

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700 text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('Date') }}</th>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('Window') }}</th>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('Mode') }}</th>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('Slots') }}</th>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('Campaign') }}</th>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('Church') }}</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($this->schedules as $schedule)
                    <tr wire:key="sch-{{ $schedule->id }}">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.prayer-schedules.edit', $schedule) }}" class="font-medium text-[#c8202f] hover:underline dark:text-rose-300" wire:navigate>
                                {{ $schedule->date->isoFormat('LL') }}
                            </a>
                        </td>
                        <td class="px-4 py-3">{{ \Illuminate\Support\Str::of($schedule->start_time)->limit(5, '') }} – {{ \Illuminate\Support\Str::of($schedule->end_time)->limit(5, '') }}</td>
                        <td class="px-4 py-3">
                            <flux:badge :color="$schedule->mode->value === 'presential' ? 'sky' : 'zinc'">
                                {{ $schedule->mode->label() }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3">{{ $schedule->slots_count }}</td>
                        <td class="px-4 py-3">
                            @if ($schedule->campaign)
                                <flux:badge color="amber">{{ $schedule->campaign->name }}</flux:badge>
                            @else
                                <span class="text-xs text-zinc-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $schedule->church?->name }}</td>
                        <td class="px-4 py-3 text-end">
                            <flux:button wire:click="delete({{ $schedule->id }})" wire:confirm="{{ __('Delete this schedule?') }}" size="sm" variant="ghost" icon="trash" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-zinc-500">{{ __('No schedules yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $this->schedules->links() }}</div>
</div>