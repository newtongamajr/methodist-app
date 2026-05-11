<div class="mx-auto max-w-5xl space-y-6 px-4 py-10 sm:px-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('Fasting calendar') }}</flux:heading>

        @if ($this->campaigns->count() > 1)
            <flux:select wire:model.live="campaignId" class="min-w-56">
                @foreach ($this->campaigns as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </flux:select>
        @endif
    </div>

    @if (! $this->campaign)
        <flux:text class="text-zinc-500">
            {{ __('There is no active fasting campaign right now.') }}
        </flux:text>
    @else
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <flux:heading size="lg">{{ $this->campaign->name }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500">
                        {{ $this->campaign->start_date->isoFormat('LL') }} → {{ $this->campaign->end_date->isoFormat('LL') }}
                    </flux:text>
                </div>
                <div class="flex items-center gap-2">
                    <flux:tooltip :content="__('Previous month')">
                        <flux:button wire:click="previousMonth" size="sm" variant="ghost" icon="chevron-left" />
                    </flux:tooltip>
                    <flux:heading>
                        {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->isoFormat('MMMM YYYY') }}
                    </flux:heading>
                    <flux:tooltip :content="__('Next month')">
                        <flux:button wire:click="nextMonth" size="sm" variant="ghost" icon="chevron-right" />
                    </flux:tooltip>
                </div>
            </div>
            @if ($this->campaign->description)
                <flux:text class="mt-2 text-sm">{{ $this->campaign->description }}</flux:text>
            @endif
        </div>

        <div class="grid grid-cols-7 gap-1 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500">
            @foreach ([__('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat')] as $day)
                <div>{{ $day }}</div>
            @endforeach
        </div>

        <div class="grid grid-cols-7 gap-1">
            @php
                $entries = $this->entries;
                $participants = $this->participantsByDate;
            @endphp
            @foreach ($this->calendar as $cell)
                @php
                    $entry = $entries[$cell['date']] ?? null;
                    $hasEntry = (bool) $entry;
                    $clickable = $cell['inCampaign'];
                    $color = $entry?->type?->color();
                    $count = (int) ($participants[$cell['date']] ?? 0);
                @endphp
                <button
                    wire:key="cal-{{ $cell['date'] }}"
                    type="button"
                    @if ($clickable) wire:click="openDay('{{ $cell['date'] }}')" @else disabled @endif
                    style="{{ $hasEntry ? 'border-color: '.$color.'; background-color: '.$color.'1A;' : '' }}"
                    @class([
                        'relative aspect-square rounded-md border p-2 text-start transition',
                        'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' => ! $hasEntry && $clickable,
                        'border-zinc-100 bg-zinc-50 cursor-not-allowed dark:border-zinc-800 dark:bg-zinc-950' => ! $clickable,
                        'opacity-40' => ! $cell['inMonth'] || ! $clickable,
                        'ring-2 ring-amber-400' => $cell['isToday'],
                    ])
                >
                    <div class="text-base font-semibold">{{ $cell['day'] }}</div>

                    @if ($hasEntry)
                        <div
                            class="mt-1 inline-block rounded-sm px-1.5 py-0.5 text-sm font-semibold leading-tight text-white"
                            style="background-color: {{ $color }};"
                            title="{{ $entry->type->label() }}"
                        >
                            {{ \Illuminate\Support\Str::limit($entry->type->label(), 18, '…') }}
                        </div>
                    @endif

                    @if ($clickable && $count > 0)
                        <span
                            class="absolute right-1 top-1 inline-flex min-w-5 items-center justify-center rounded-full bg-zinc-900/85 px-1.5 py-0.5 text-[11px] font-bold text-white shadow-xs dark:bg-white/85 dark:text-zinc-900"
                            title="{{ trans_choice(':count member fasting|:count members fasting', $count) }}"
                        >
                            {{ $count }}
                        </span>
                    @endif
                </button>
            @endforeach
        </div>

        <flux:modal wire:model.self="isModalOpen" name="fasting-day" class="md:w-lg">
            @if ($editingDate)
                <form wire:submit="save" class="space-y-5">
                    <flux:heading size="lg">
                        {{ \Illuminate\Support\Carbon::parse($editingDate)->isoFormat('LL') }}
                    </flux:heading>

                    <flux:select wire:model="type" :label="__('Fasting type')" required>
                        @foreach ($this->allowedTypes as $t)
                            <option value="{{ $t->value }}">{{ $t->label() }}</option>
                        @endforeach
                    </flux:select>

                    @if (count($this->allowedRestrictions) > 0)
                        <div>
                            <flux:label>{{ __('Restrictions') }}</flux:label>
                            <div class="mt-2 grid grid-cols-2 gap-2">
                                @foreach ($this->allowedRestrictions as $r)
                                    <label class="flex items-center gap-2 rounded-md border border-zinc-200 p-2 text-sm dark:border-zinc-700">
                                        <input
                                            type="checkbox"
                                            value="{{ $r->value }}"
                                            wire:model="restrictions"
                                            class="rounded-sm text-accent focus:ring-accent"
                                        >
                                        {{ $r->label() }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <flux:textarea wire:model="notes" :label="__('Notes')" rows="2" />

                    <div class="flex items-center justify-between gap-2 pt-2">
                        @if (isset($entries[$editingDate]))
                            <flux:modal.trigger name="remove-fasting-entry">
                                <flux:button type="button" variant="ghost">{{ __('Remove') }}</flux:button>
                            </flux:modal.trigger>
                            <x-confirm-delete
                                name="remove-fasting-entry"
                                :heading="__('Remove this entry?')"
                                :confirmLabel="__('Remove')"
                                action="delete('{{ $editingDate }}')"
                            />
                        @else
                            <span></span>
                        @endif

                        <div class="flex gap-2">
                            <flux:modal.close>
                                <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                            </flux:modal.close>
                            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">{{ __('Save') }}</flux:button>
                        </div>
                    </div>
                </form>
            @endif
        </flux:modal>
    @endif
</div>