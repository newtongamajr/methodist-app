<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('Churches') }}</flux:heading>
        <flux:button :href="route('admin.churches.create')" variant="primary" icon="plus" wire:navigate>
            {{ __('New church') }}
        </flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search by name or city…')" icon="magnifying-glass" />

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700 text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('Name') }}</th>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('Type') }}</th>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('Region') }}</th>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('City') }}</th>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('Members') }}</th>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('Active') }}</th>
                    <th class="px-4 py-2 text-end font-semibold">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($this->churches as $church)
                    <tr wire:key="church-{{ $church->id }}">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.churches.edit', $church) }}" class="font-medium text-[#c8202f] hover:underline dark:text-rose-300" wire:navigate>
                                {{ $church->name }}
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge :color="$church->type?->value === 'missionary_point' ? 'amber' : 'sky'">
                                {{ $church->type?->label() }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3"><flux:badge color="zinc">{{ $church->region?->code }}</flux:badge></td>
                        <td class="px-4 py-3">{{ $church->city ? $church->city.'/'.$church->state : '—' }}</td>
                        <td class="px-4 py-3">{{ $church->primary_users_count }}</td>
                        <td class="px-4 py-3">
                            <flux:badge :color="$church->is_active ? 'emerald' : 'zinc'">
                                {{ $church->is_active ? __('Active') : __('Inactive') }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="inline-flex items-center gap-1">
                                <flux:tooltip :content="__('Edit')">
                                    <flux:button :href="route('admin.churches.edit', $church)" wire:navigate size="sm" variant="ghost" icon="pencil-square" />
                                </flux:tooltip>
                                <flux:tooltip :content="__('Prayer schedules')">
                                    <flux:button :href="route('admin.prayer-schedules.index', ['church' => $church->id])" wire:navigate size="sm" variant="ghost" icon="clock" />
                                </flux:tooltip>
                                <flux:tooltip :content="__('Pastors')">
                                    <flux:button :href="route('admin.churches.pastors.index', $church)" wire:navigate size="sm" variant="ghost" icon="user-group" />
                                </flux:tooltip>
                                <flux:tooltip :content="__('Administrators')">
                                    <flux:button :href="route('admin.users.index', ['church' => $church->id])" wire:navigate size="sm" variant="ghost" icon="users" />
                                </flux:tooltip>
                                <flux:tooltip :content="__('Delete')">
                                    <flux:button wire:click="delete({{ $church->id }})" wire:confirm="{{ __('Delete this church and all its data?') }}" size="sm" variant="ghost" icon="trash" />
                                </flux:tooltip>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-zinc-500">{{ __('No churches yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $this->churches->links() }}</div>
</div>
