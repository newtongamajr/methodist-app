<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('Members') }}</flux:heading>
        <flux:button :href="route('admin.members.create')" variant="primary" icon="plus" wire:navigate>
            {{ __('New member') }}
        </flux:button>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search by name or email…')" icon="magnifying-glass" />

        <flux:select wire:model.live="churchFilter">
            <option value="">{{ __('All my churches') }}</option>
            @foreach ($this->churches as $church)
                <option value="{{ $church['id'] }}">{{ $church['name'] }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="memberTypeFilter">
            <option value="">{{ __('All member types') }}</option>
            @foreach (\App\Enums\MemberType::cases() as $type)
                <option value="{{ $type->value }}">{{ $type->label() }}</option>
            @endforeach
        </flux:select>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700 text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('Name') }}</th>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('Email') }}</th>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('I am a') }}</th>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('Churches') }}</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($this->members as $user)
                    <tr wire:key="member-{{ $user->id }}">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.members.edit', $user) }}" class="font-medium text-[#c8202f] hover:underline dark:text-rose-300" wire:navigate>
                                {{ $user->name }}
                            </a>
                        </td>
                        <td class="px-4 py-3">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            @if ($user->member_type)
                                <flux:badge color="zinc">{{ $user->member_type->label() }}</flux:badge>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                @forelse ($user->churches as $c)
                                    <flux:badge :color="$user->church_id === $c->id ? 'emerald' : 'zinc'">
                                        {{ $c->name }}
                                    </flux:badge>
                                @empty
                                    —
                                @endforelse
                            </div>
                        </td>
                        <td class="px-4 py-3 text-end">
                            <flux:button wire:click="delete({{ $user->id }})" wire:confirm="{{ __('Delete this member?') }}" size="sm" variant="ghost" icon="trash" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-zinc-500">{{ __('No members yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $this->members->links() }}</div>
</div>