<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('Groups') }}</flux:heading>
        <flux:button :href="route('admin.groups.create', $kindFilter ? ['kind' => $kindFilter] : [])" variant="primary" icon="plus" wire:navigate>
            {{ __('New group') }}
        </flux:button>
    </div>

    <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
        <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search by name or slug…')" icon="magnifying-glass" class="lg:col-span-2" />

        <flux:select wire:model.live="kindFilter" variant="listbox" clearable :placeholder="__('All kinds')">
            @foreach (\App\Enums\GroupKind::cases() as $kind)
                <flux:select.option :value="$kind->value">{{ $kind->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="levelFilter" variant="listbox" clearable :placeholder="__('All levels')">
            <flux:select.option value="national">{{ __('National') }}</flux:select.option>
            <flux:select.option value="region">{{ __('Region') }}</flux:select.option>
            <flux:select.option value="district">{{ __('District') }}</flux:select.option>
            <flux:select.option value="church">{{ __('Church') }}</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="regionFilter" variant="listbox" searchable clearable :placeholder="__('All regions')">
            @foreach ($this->regions as $region)
                <flux:select.option :value="$region->id">{{ $region->code }} — {{ $region->name }}</flux:select.option>
            @endforeach
        </flux:select>

        @if ($regionFilter)
            <flux:select wire:model.live="districtFilter" variant="listbox" searchable clearable :placeholder="__('All districts')">
                @foreach ($this->districts as $district)
                    <flux:select.option :value="$district->id">{{ $district->name }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif
    </div>

    @if ($this->groups->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
            {{ __('No groups yet.') }}
        </div>
    @else
        <flux:table :paginate="$this->groups">
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDir" wire:click="sort('name')">{{ __('Name') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'kind'" :direction="$sortDir" wire:click="sort('kind')">{{ __('Kind') }}</flux:table.column>
                <flux:table.column>{{ __('Scope') }}</flux:table.column>
                <flux:table.column>{{ __('Members') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'is_active'" :direction="$sortDir" wire:click="sort('is_active')">{{ __('Active') }}</flux:table.column>
                <flux:table.column align="end">&nbsp;</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->groups as $group)
                    <flux:table.row :key="'group-'.$group->id">
                        <flux:table.cell variant="strong">{{ $group->name }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="match($group->kind?->value) { 'council' => 'sky', 'ministry' => 'emerald', 'commission' => 'amber', default => 'zinc' }">
                                {{ $group->kind?->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($group->church)
                                <span class="inline-flex items-center gap-1"><flux:icon.building-library class="size-4 text-zinc-400" /> {{ $group->church->name }}</span>
                            @elseif ($group->district)
                                <span class="inline-flex items-center gap-1"><flux:icon.map class="size-4 text-zinc-400" /> {{ $group->district->name }}</span>
                            @elseif ($group->region)
                                <span class="inline-flex items-center gap-1"><flux:icon.globe-americas class="size-4 text-zinc-400" /> {{ $group->region->code }}</span>
                            @else
                                <flux:badge color="rose">{{ __('National') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $group->members_count }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$group->is_active ? 'emerald' : 'zinc'">
                                {{ $group->is_active ? __('Active') : __('Inactive') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="inline-flex items-center gap-1">
                                <flux:tooltip :content="__('Edit')">
                                    <flux:button :href="route('admin.groups.edit', $group)" wire:navigate size="sm" variant="ghost" icon="pencil-square" />
                                </flux:tooltip>
                                <flux:tooltip :content="__('Delete')">
                                    <flux:modal.trigger :name="'delete-group-'.$group->id">
                                        <flux:button size="sm" variant="ghost" icon="trash" />
                                    </flux:modal.trigger>
                                </flux:tooltip>
                                <x-confirm-delete
                                    :name="'delete-group-'.$group->id"
                                    :heading="__('Delete this group and end every member assignment?')"
                                    action="delete({{ $group->id }})"
                                />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
