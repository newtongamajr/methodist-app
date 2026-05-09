<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('People') }}</flux:heading>
        <flux:button :href="route('admin.people.create')" variant="primary" icon="plus" wire:navigate>
            {{ __('New person') }}
        </flux:button>
    </div>

    @error('person')
        <div class="rounded-md bg-rose-50 p-3 text-sm font-medium text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">{{ $message }}</div>
    @enderror

    <div class="grid gap-3 sm:grid-cols-3">
        <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search by name or tax ID…')" icon="magnifying-glass" />

        @if (! empty($this->availableNatures))
            <flux:select
                wire:model.live="natureFilter"
                variant="listbox"
                searchable
                clearable
                :placeholder="__('All natures')"
            >
                @foreach ($this->availableNatures as $value => $label)
                    <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif

        <div class="flex items-center">
            <flux:checkbox wire:model.live="includeOrganizations" :label="__('Include organizations (regions, districts, churches)')" />
        </div>
    </div>

    @if ($this->persons->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
            {{ __('No people yet.') }}
        </div>
    @else
        <flux:table :paginate="$this->persons">
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDir" wire:click="sort('name')">{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Natures') }}</flux:table.column>
                <flux:table.column>{{ __('Tax ID') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'birthdate'" :direction="$sortDir" wire:click="sort('birthdate')">{{ __('Birthdate') }}</flux:table.column>
                <flux:table.column>{{ __('Managing church') }}</flux:table.column>
                <flux:table.column align="end">&nbsp;</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->persons as $person)
                    <flux:table.row :key="'person-'.$person->id">
                        <flux:table.cell variant="strong">
                            {{ $person->name }}
                            @if ($person->preferred_name)
                                <span class="ms-1 text-xs text-zinc-500">({{ $person->preferred_name }})</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-wrap gap-1">
                                @forelse ($person->natures ?? [] as $nature)
                                    <flux:badge wire:key="person-{{ $person->id }}-nat-{{ $nature }}" color="zinc">
                                        {{ \App\Enums\PersonNature::tryFrom($nature)?->label() ?? $nature }}
                                    </flux:badge>
                                @empty
                                    —
                                @endforelse
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="font-mono text-xs">{{ $person->tax_id ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $person->birthdate?->isoFormat('LL') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $person->managingChurch?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="inline-flex items-center gap-1">
                                <flux:tooltip :content="__('Edit')">
                                    <flux:button :href="route('admin.people.edit', $person)" wire:navigate size="sm" variant="ghost" icon="pencil-square" />
                                </flux:tooltip>
                                <flux:tooltip :content="__('Delete')">
                                    <flux:button wire:click="deletePerson({{ $person->id }})" wire:confirm="{{ __('Delete this person?') }}" size="sm" variant="ghost" icon="trash" />
                                </flux:tooltip>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
