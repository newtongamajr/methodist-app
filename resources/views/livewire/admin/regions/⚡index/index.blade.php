<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('Ecclesiastical regions') }}</flux:heading>
        <flux:button :href="route('admin.regions.create')" variant="primary" icon="plus" wire:navigate>
            {{ __('New region') }}
        </flux:button>
    </div>

    @error('region')
        <div class="rounded-md bg-rose-50 p-3 text-sm font-medium text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">{{ $message }}</div>
    @enderror

    @if ($this->regions->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
            {{ __('No regions yet.') }}
        </div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'display_order'" :direction="$sortDir" wire:click="sort('display_order')">{{ __('Order') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'code'" :direction="$sortDir" wire:click="sort('code')">{{ __('Code') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDir" wire:click="sort('name')">{{ __('Name') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'kind'" :direction="$sortDir" wire:click="sort('kind')">{{ __('Kind') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'churches_count'" :direction="$sortDir" wire:click="sort('churches_count')">{{ __('Churches') }}</flux:table.column>
                <flux:table.column align="end">&nbsp;</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->regions as $region)
                    <flux:table.row :key="'region-'.$region->id">
                        <flux:table.cell>{{ $region->display_order }}</flux:table.cell>
                        <flux:table.cell><flux:badge color="zinc">{{ $region->code }}</flux:badge></flux:table.cell>
                        <flux:table.cell variant="strong">{{ $region->name }}</flux:table.cell>
                        <flux:table.cell>{{ $region->kind?->label() }}</flux:table.cell>
                        <flux:table.cell>{{ $region->churches_count }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="inline-flex items-center gap-1">
                                <flux:tooltip :content="__('Edit')">
                                    <flux:button :href="route('admin.regions.edit', $region)" wire:navigate size="sm" variant="ghost" icon="pencil-square" />
                                </flux:tooltip>
                                <flux:tooltip :content="__('Districts in this region')">
                                    <flux:button :href="route('admin.districts.index', ['region' => $region->id])" wire:navigate size="sm" variant="ghost" icon="map" />
                                </flux:tooltip>
                                <flux:tooltip :content="__('Churches in this region')">
                                    <flux:button :href="route('admin.churches.index', ['region' => $region->id])" wire:navigate size="sm" variant="ghost" icon="building-library" />
                                </flux:tooltip>
                                <flux:tooltip :content="__('Delete')">
                                    <flux:button wire:click="delete({{ $region->id }})" wire:confirm="{{ __('Delete this region?') }}" size="sm" variant="ghost" icon="trash" />
                                </flux:tooltip>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>