<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('Districts') }}</flux:heading>
        <flux:button :href="route('admin.districts.create', $regionFilter ? ['region' => $regionFilter] : [])" variant="primary" icon="plus" wire:navigate>
            {{ __('New district') }}
        </flux:button>
    </div>

    @error('district')
        <div class="rounded-md bg-rose-50 p-3 text-sm font-medium text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">{{ $message }}</div>
    @enderror

    <flux:select
        wire:model.live="regionFilter"
        variant="listbox"
        searchable
        clearable
        :label="__('Filter by region')"
        :placeholder="__('All regions')"
    >
        @foreach ($this->regions as $region)
            <flux:select.option :value="$region->id">{{ $region->code }} — {{ $region->name }}</flux:select.option>
        @endforeach
    </flux:select>

    @if ($this->districts->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
            {{ __('No districts yet.') }}
        </div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'display_order'" :direction="$sortDir" wire:click="sort('display_order')">{{ __('Order') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDir" wire:click="sort('name')">{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Region') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'churches_count'" :direction="$sortDir" wire:click="sort('churches_count')">{{ __('Churches') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'is_active'" :direction="$sortDir" wire:click="sort('is_active')">{{ __('Active') }}</flux:table.column>
                <flux:table.column align="end">&nbsp;</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->districts as $district)
                    <flux:table.row :key="'district-'.$district->id">
                        <flux:table.cell>{{ $district->display_order }}</flux:table.cell>
                        <flux:table.cell variant="strong">{{ $district->name }}</flux:table.cell>
                        <flux:table.cell><flux:badge color="zinc">{{ $district->region?->code }}</flux:badge></flux:table.cell>
                        <flux:table.cell>{{ $district->churches_count }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$district->is_active ? 'emerald' : 'zinc'">
                                {{ $district->is_active ? __('Active') : __('Inactive') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="inline-flex items-center gap-1">
                                <flux:tooltip :content="__('Edit')">
                                    <flux:button :href="route('admin.districts.edit', $district)" wire:navigate size="sm" variant="ghost" icon="pencil-square" />
                                </flux:tooltip>
                                <flux:tooltip :content="__('Churches in this district')">
                                    <flux:button
                                        :href="route('admin.churches.index', ['region' => $district->ecclesiastical_region_id, 'district' => $district->id])"
                                        wire:navigate
                                        size="sm"
                                        variant="ghost"
                                        icon="building-library"
                                    />
                                </flux:tooltip>
                                <flux:tooltip :content="__('Delete')">
                                    <flux:button wire:click="delete({{ $district->id }})" wire:confirm="{{ __('Delete this district?') }}" size="sm" variant="ghost" icon="trash" />
                                </flux:tooltip>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
