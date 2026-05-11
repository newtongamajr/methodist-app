<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('Churches') }}</flux:heading>
        <flux:button :href="route('admin.churches.create')" variant="primary" icon="plus" wire:navigate>
            {{ __('New church') }}
        </flux:button>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search by name or city…')" icon="magnifying-glass" />

        <flux:select
            wire:model.live="regionFilter"
            variant="listbox"
            searchable
            clearable
            :placeholder="__('All regions')"
        >
            @foreach ($this->regions as $region)
                <flux:select.option :value="$region->id">{{ $region->code }} — {{ $region->name }}</flux:select.option>
            @endforeach
        </flux:select>

        @if ($regionFilter)
            <flux:select
                wire:model.live="districtFilter"
                variant="listbox"
                searchable
                clearable
                :placeholder="$this->districts->isEmpty() ? __('No districts in this region yet.') : __('All districts')"
                :disabled="$this->districts->isEmpty()"
            >
                @foreach ($this->districts as $district)
                    <flux:select.option :value="$district->id">{{ $district->name }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif
    </div>

    @if ($this->churches->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
            {{ __('No churches yet.') }}
        </div>
    @else
        <flux:table :paginate="$this->churches">
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDir" wire:click="sort('name')">{{ __('Name') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'type'" :direction="$sortDir" wire:click="sort('type')">{{ __('Type') }}</flux:table.column>
                <flux:table.column>{{ __('Region') }}</flux:table.column>
                <flux:table.column>{{ __('District') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'city'" :direction="$sortDir" wire:click="sort('city')">{{ __('City') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'members_count'" :direction="$sortDir" wire:click="sort('members_count')">{{ __('Members') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'is_active'" :direction="$sortDir" wire:click="sort('is_active')">{{ __('Active') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->churches as $church)
                    <flux:table.row :key="'church-'.$church->id">
                        <flux:table.cell variant="strong">{{ $church->name }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$church->type?->value === 'missionary_point' ? 'amber' : 'sky'">
                                {{ $church->type?->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell><flux:badge color="zinc">{{ $church->region?->code }}</flux:badge></flux:table.cell>
                        <flux:table.cell>{{ $church->district?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $church->city ? $church->city.'/'.$church->state : '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $church->members_count }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$church->is_active ? 'emerald' : 'zinc'">
                                {{ $church->is_active ? __('Active') : __('Inactive') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
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
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
