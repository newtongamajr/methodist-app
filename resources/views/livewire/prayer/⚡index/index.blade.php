<div class="mx-auto max-w-5xl space-y-8 px-4 py-10 sm:px-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('Prayer schedule') }}</flux:heading>

        @if ($churchId && $this->campaigns->count() > 0)
            <flux:select wire:model.live="campaignId" class="min-w-[16rem]">
                @foreach ($this->campaigns as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </flux:select>
        @endif
    </div>

    @if (! $churchId)
        <flux:text class="text-zinc-500">
            {{ __('Pick a church in your profile to see prayer slots.') }}
            <a href="{{ route('profile') }}" class="font-medium text-accent hover:underline" wire:navigate>{{ __('Open profile') }}</a>
        </flux:text>
    @elseif (! $this->campaign)
        <flux:text class="text-zinc-500">
            {{ __('There is no active prayer campaign right now.') }}
        </flux:text>
    @else
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ $this->campaign->name }}</flux:heading>
            <flux:text class="text-sm text-zinc-500">
                {{ $this->campaign->start_date->isoFormat('LL') }} → {{ $this->campaign->end_date->isoFormat('LL') }}
            </flux:text>
            @if ($this->campaign->description)
                <flux:text class="mt-2 text-sm">{{ $this->campaign->description }}</flux:text>
            @endif
            @if ($this->campaign->objectives)
                <flux:accordion class="mt-3 text-sm">
                    <flux:accordion.item :heading="__('Objectives')">
                        <flux:accordion.content class="whitespace-pre-line text-zinc-700 dark:text-zinc-300">{{ $this->campaign->objectives }}</flux:accordion.content>
                    </flux:accordion.item>
                </flux:accordion>
            @endif
        </div>

        {{-- Suggestions --}}
        @if ($this->suggestions->isNotEmpty())
            <section class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800/50 dark:bg-amber-900/20">
                <flux:heading size="lg" class="text-amber-800! dark:text-amber-200!">
                    {{ __('Slots needing more prayers') }}
                </flux:heading>
                <flux:text class="mt-1 text-sm">
                    {{ __('These upcoming slots are below 30% coverage. Consider joining one of them.') }}
                </flux:text>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($this->suggestions as $slot)
                        <button type="button" wire:key="suggest-slot-{{ $slot->id }}" wire:click="join({{ $slot->id }})"
                                class="rounded-md bg-amber-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-amber-600">
                            {{ $slot->starts_at->isoFormat('ddd, D MMM HH:mm') }}
                        </button>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Day calendar --}}
        <section class="space-y-4 rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <flux:button wire:click="previousDay" size="sm" variant="ghost" icon="chevron-left" />
                    <flux:heading size="lg">
                        {{ $selectedDate ? \Illuminate\Support\Carbon::parse($selectedDate)->isoFormat('dddd, LL') : __('No prayer slots scheduled') }}
                    </flux:heading>
                    <flux:button wire:click="nextDay" size="sm" variant="ghost" icon="chevron-right" />
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <flux:select wire:model.live="selectedDate">
                        @foreach ($this->days as $day)
                            <option value="{{ $day }}">{{ \Illuminate\Support\Carbon::parse($day)->isoFormat('ddd, LL') }}</option>
                        @endforeach
                    </flux:select>
                </div>
            </div>

            {{-- Filters --}}
            <div class="flex flex-wrap items-center gap-2 border-t border-zinc-100 pt-3 dark:border-zinc-800">
                <span class="text-sm font-medium text-zinc-500">{{ __('Show') }}:</span>
                <flux:button.group>
                    <flux:button
                        wire:click="$set('coverageFilter', 'all')"
                        size="sm"
                        :variant="$coverageFilter === 'all' ? 'primary' : 'ghost'"
                    >
                        {{ __('All slots') }}
                    </flux:button>
                    <flux:button
                        wire:click="$set('coverageFilter', 'mine')"
                        size="sm"
                        :variant="$coverageFilter === 'mine' ? 'primary' : 'ghost'"
                    >
                        {{ __('My slots') }}
                    </flux:button>
                    <flux:button
                        wire:click="$set('coverageFilter', 'user')"
                        size="sm"
                        :variant="$coverageFilter === 'user' ? 'primary' : 'ghost'"
                    >
                        {{ __('Specific member') }}
                    </flux:button>
                </flux:button.group>

                @if ($coverageFilter === 'user')
                    <flux:select wire:model.live="userFilterId" :placeholder="__('Pick a user…')" class="min-w-48">
                        <option value="">{{ __('— Select —') }}</option>
                        @foreach ($this->churchUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </flux:select>
                @endif
            </div>

            @error('slot')
                <div class="rounded-md bg-rose-50 p-2 text-sm text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">
                    {{ $message }}
                </div>
            @enderror

            {{-- Slot list --}}
            @php
                $slots = $this->daySlots;
                $mine = $this->mySignups;
                $myId = auth()->id();
            @endphp

            @if ($slots->isEmpty())
                <flux:text class="text-zinc-500">{{ __('No prayer slots scheduled for this day.') }}</flux:text>
            @else
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($slots as $slot)
                        @php
                            $userIds = $slot->confirmedSignups->pluck('user_id')->all();
                            $matchesFilter = match ($coverageFilter) {
                                'mine' => in_array($slot->id, $mine, true),
                                'user' => $userFilterId && in_array((int) $userFilterId, $userIds, true),
                                default => true,
                            };
                            $remaining = max(0, $slot->capacity - $slot->confirmedSignups->count());
                            $isMine = in_array($slot->id, $mine, true);
                            $isFull = $remaining <= 0;
                            $isPast = $slot->starts_at->isPast();
                        @endphp

                        <div
                            wire:key="day-slot-{{ $slot->id }}"
                            @class([
                                'flex flex-wrap items-start gap-3 py-3',
                                'opacity-50' => ! $matchesFilter,
                            ])
                        >
                            {{-- Time column --}}
                            <div class="w-28 shrink-0">
                                <div @class([
                                    'rounded-md px-2 py-1 text-center text-sm font-semibold',
                                    'bg-accent/10 text-accent dark:bg-rose-500/15 dark:text-rose-300' => $isMine,
                                    'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200' => ! $isMine,
                                ])>
                                    {{ $slot->starts_at->format('H:i') }} – {{ $slot->ends_at->format('H:i') }}
                                </div>
                                <div class="mt-1 text-center text-[11px] text-zinc-500">
                                    {{ $slot->mode->label() }}
                                </div>
                            </div>

                            {{-- Members column --}}
                            <div class="min-w-0 flex-1">
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="text-xs font-medium text-zinc-500">
                                        {{ $slot->confirmedSignups->count() }}/{{ $slot->capacity }}
                                        @if ($remaining > 0 && ! $isPast)
                                            · {{ trans_choice(':count seat left|:count seats left', $remaining) }}
                                        @endif
                                    </span>
                                </div>
                                @if ($slot->confirmedSignups->isEmpty())
                                    <span class="text-xs italic text-zinc-400">
                                        {{ __('No one signed up yet — be the first.') }}
                                    </span>
                                @else
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($slot->confirmedSignups as $signup)
                                            <span
                                                wire:key="signup-{{ $signup->id }}"
                                                @class([
                                                    'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium',
                                                    'bg-accent/10 text-accent dark:bg-rose-500/15 dark:text-rose-300' => $signup->user_id === $myId,
                                                    'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200' => $coverageFilter === 'user' && $signup->user_id === (int) $userFilterId && $signup->user_id !== $myId,
                                                    'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200' => $signup->user_id !== $myId && ! ($coverageFilter === 'user' && $signup->user_id === (int) $userFilterId),
                                                ])
                                            >
                                                {{ $signup->user?->name ?? '—' }}
                                                @if ($this->isAdminHere && $signup->user_id !== $myId && ! $isPast)
                                                    <button
                                                        type="button"
                                                        wire:click="removeSignup({{ $signup->id }})"
                                                        wire:confirm="{{ __('Remove :name from this slot?', ['name' => $signup->user?->name ?? '—']) }}"
                                                        class="ml-0.5 -mr-1 size-4 rounded-full hover:bg-black/10 dark:hover:bg-white/10"
                                                        title="{{ __('Remove from slot') }}"
                                                    >
                                                        ×
                                                    </button>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                @if ($this->isAdminHere && ! $isPast && ! $isFull)
                                    <div class="mt-2 flex items-center gap-2">
                                        <flux:select
                                            wire:model="assignChoice.{{ $slot->id }}"
                                            class="min-w-40"
                                            size="sm"
                                        >
                                            <option value="">{{ __('Add a member to this slot…') }}</option>
                                            @foreach ($this->attachableUsers as $u)
                                                @if (! in_array($u->id, $userIds, true))
                                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                                @endif
                                            @endforeach
                                        </flux:select>
                                        <flux:button
                                            wire:click="addAssigned({{ $slot->id }})"
                                            size="sm"
                                            variant="ghost"
                                            icon="plus"
                                        />
                                    </div>
                                @endif
                            </div>

                            {{-- Action column --}}
                            <div class="shrink-0">
                                @if ($isMine)
                                    <flux:button size="sm" variant="ghost" wire:click="leave({{ $slot->id }})">
                                        {{ __('Leave') }}
                                    </flux:button>
                                @elseif ($isPast)
                                    <flux:badge color="zinc">{{ __('Past') }}</flux:badge>
                                @elseif ($isFull)
                                    <flux:badge color="zinc">{{ __('Full') }}</flux:badge>
                                @else
                                    <flux:button size="sm" variant="primary" wire:click="join({{ $slot->id }})">
                                        {{ __('Take this slot') }}
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
</div>