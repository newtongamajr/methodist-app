<div>
    <div class="mb-4 flex items-center justify-between gap-4">
        <flux:heading size="lg">{{ __('Roles') }}</flux:heading>
        <flux:button wire:click="openCreate" variant="primary" icon="plus" size="sm">{{ __('Add role') }}</flux:button>
    </div>

    @if ($this->assignments->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
            {{ __('No roles assigned yet.') }}
        </div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Function') }}</flux:table.column>
                <flux:table.column>{{ __('Scope') }}</flux:table.column>
                <flux:table.column>{{ __('Started') }}</flux:table.column>
                <flux:table.column>{{ __('Ended') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->assignments as $a)
                    <flux:table.row :key="'role-'.$a->id">
                        <flux:table.cell variant="strong">{{ $a->function?->name }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($a->church)
                                <span class="inline-flex items-center gap-1">
                                    <flux:icon.building-library class="size-4 text-zinc-400" />
                                    {{ $a->church->name }}
                                </span>
                            @elseif ($a->group)
                                <span class="inline-flex items-center gap-1">
                                    <flux:icon.user-group class="size-4 text-zinc-400" />
                                    {{ $a->group->name }}
                                </span>
                            @elseif ($a->district)
                                <span class="inline-flex items-center gap-1">
                                    <flux:icon.map class="size-4 text-zinc-400" />
                                    {{ $a->district->name }}
                                </span>
                            @elseif ($a->region)
                                <span class="inline-flex items-center gap-1">
                                    <flux:icon.globe-americas class="size-4 text-zinc-400" />
                                    {{ $a->region->code }} — {{ $a->region->name }}
                                </span>
                            @else
                                <flux:badge color="rose">{{ __('National') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $a->started_at?->isoFormat('LL') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($a->ended_at)
                                {{ $a->ended_at->isoFormat('LL') }}
                            @else
                                <flux:badge color="emerald">{{ __('Active') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="inline-flex items-center gap-1">
                                <flux:tooltip :content="__('Edit')">
                                    <flux:button wire:click="openEdit({{ $a->id }})" size="sm" variant="ghost" icon="pencil-square" />
                                </flux:tooltip>
                                @if (! $a->ended_at)
                                    <flux:tooltip :content="__('End today')">
                                        <flux:modal.trigger :name="'end-role-'.$a->id">
                                            <flux:button size="sm" variant="ghost" icon="x-circle" />
                                        </flux:modal.trigger>
                                    </flux:tooltip>
                                    <x-confirm-delete
                                        :name="'end-role-'.$a->id"
                                        :heading="__('End this role today?')"
                                        :confirmLabel="__('End')"
                                        action="endAssignment({{ $a->id }})"
                                    />
                                @endif
                                <flux:tooltip :content="__('Delete')">
                                    <flux:modal.trigger :name="'delete-role-'.$a->id">
                                        <flux:button size="sm" variant="ghost" icon="trash" />
                                    </flux:modal.trigger>
                                </flux:tooltip>
                                <x-confirm-delete
                                    :name="'delete-role-'.$a->id"
                                    :heading="__('Delete this role?')"
                                    action="delete({{ $a->id }})"
                                />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif

    <flux:modal wire:model.self="showModal" class="md:max-w-xl">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $form->assignment ? __('Edit role') : __('Add role') }}</flux:heading>

            <flux:select
                wire:model.live="form.function_id"
                variant="listbox"
                searchable
                :label="__('Function')"
                :placeholder="__('Pick a function…')"
                required
            >
                @foreach ($this->functions as $fn)
                    <flux:select.option :value="$fn->id">
                        {{ $fn->name }}
                        @if ($fn->max_holders)
                            <span class="text-xs text-zinc-500">({{ __('max :n', ['n' => $fn->max_holders]) }})</span>
                        @endif
                    </flux:select.option>
                @endforeach
            </flux:select>

            @php $kind = $this->selectedFunction ? $this->scopeKindFor($this->selectedFunction) : null; @endphp

            @if ($kind === 'church')
                <flux:select
                    wire:model="form.church_id"
                    variant="listbox"
                    searchable
                    clearable
                    :label="__('Church')"
                    :placeholder="__('Search a church by name…')"
                    required
                >
                    @foreach ($this->churches as $c)
                        <flux:select.option :value="$c->id">
                            {{ $c->name }}@if ($c->city) — {{ $c->city }}/{{ $c->state }}@endif
                        </flux:select.option>
                    @endforeach
                </flux:select>
            @elseif ($kind === 'district')
                <flux:select
                    wire:model="form.district_id"
                    variant="listbox"
                    searchable
                    clearable
                    :label="__('District')"
                    :placeholder="__('Pick a district…')"
                    required
                >
                    @foreach ($this->districts as $d)
                        <flux:select.option :value="$d->id">{{ $d->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            @elseif ($kind === 'region')
                <flux:select
                    wire:model="form.ecclesiastical_region_id"
                    variant="listbox"
                    searchable
                    clearable
                    :label="__('Ecclesiastical region')"
                    :placeholder="__('Pick a region…')"
                    required
                >
                    @foreach ($this->regions as $r)
                        <flux:select.option :value="$r->id">{{ $r->code }} — {{ $r->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            @elseif ($kind === 'group')
                <flux:select
                    wire:model="form.group_id"
                    variant="listbox"
                    searchable
                    clearable
                    :label="__('Group')"
                    :placeholder="$this->groupsForFunction->isEmpty() ? __('No matching groups for this function.') : __('Pick a group…')"
                    :disabled="$this->groupsForFunction->isEmpty()"
                    required
                >
                    @foreach ($this->groupsForFunction as $g)
                        <flux:select.option :value="$g->id">[{{ $g->kind?->label() }}] {{ $g->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            @elseif ($this->selectedFunction)
                <flux:callout variant="success" icon="check-circle" inline :heading="__('National scope — no further pick required.')" />
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:date-picker wire:model="form.started_at" :label="__('Started at')" />
                <flux:date-picker wire:model="form.ended_at" :label="__('Ended at')" />
            </div>

            <flux:checkbox wire:model="form.is_primary" :label="__('Primary role for this person')" />

            <div class="flex justify-end gap-2 pt-2">
                <flux:button type="button" variant="ghost" x-on:click="$wire.showModal = false">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
