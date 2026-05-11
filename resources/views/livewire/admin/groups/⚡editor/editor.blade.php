<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('admin.groups.index')" wire:navigate>{{ __('Groups') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $form->group ? __('Edit group') : __('New group') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">
            {{ $form->group ? $form->group->name : __('New group') }}
        </flux:heading>
        <flux:button :href="route('admin.groups.index')" variant="ghost" wire:navigate>{{ __('Back to list') }}</flux:button>
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle" inline :heading="session('status')" />
    @endif

    <form wire:submit="save" class="space-y-5">
        <div class="grid gap-4 sm:grid-cols-3">
            <flux:select wire:model.live="form.kind" :label="__('Kind')" required>
                @foreach (\App\Enums\GroupKind::cases() as $kind)
                    <option value="{{ $kind->value }}">{{ $kind->label() }}</option>
                @endforeach
            </flux:select>
            <flux:input wire:model="form.name" :label="__('Name')" class="sm:col-span-2" required />
        </div>

        <flux:input wire:model="form.slug" :label="__('Slug')" :placeholder="__('Leave blank to auto-generate')" />

        <flux:textarea wire:model="form.description" :label="__('Description')" rows="3" />

        <flux:radio.group wire:model.live="form.level" :label="__('Level')">
            <flux:radio value="national" :label="__('National')" />
            <flux:radio value="region" :label="__('Region')" />
            <flux:radio value="district" :label="__('District')" />
            <flux:radio value="church" :label="__('Church')" />
        </flux:radio.group>

        @if ($form->level !== 'national')
            <flux:select
                wire:model.live="form.ecclesiastical_region_id"
                variant="listbox"
                searchable
                clearable
                :label="__('Ecclesiastical region')"
                :placeholder="__('Pick a region…')"
                required
            >
                @foreach ($this->regions as $region)
                    <flux:select.option :value="$region->id">{{ $region->code }} — {{ $region->name }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif

        @if (in_array($form->level, ['district', 'church']) && $form->ecclesiastical_region_id)
            <flux:select
                wire:model.live="form.district_id"
                variant="listbox"
                searchable
                clearable
                :label="__('District')"
                :placeholder="$this->districts->isEmpty() ? __('No districts in this region yet.') : __('Pick a district…')"
                :disabled="$this->districts->isEmpty()"
                required
            >
                @foreach ($this->districts as $district)
                    <flux:select.option :value="$district->id">{{ $district->name }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif

        @if ($form->level === 'church' && $form->ecclesiastical_region_id)
            <flux:select
                wire:model="form.church_id"
                variant="listbox"
                searchable
                clearable
                :label="__('Church')"
                :placeholder="__('Search a church by name…')"
                required
            >
                @foreach ($this->churches as $church)
                    <flux:select.option :value="$church->id">{{ $church->name }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif

        <div class="grid gap-4 sm:grid-cols-3">
            <flux:date-picker wire:model="form.started_at" :label="__('Started at')" />
            <flux:date-picker wire:model="form.ended_at" :label="__('Ended at')" />
            <div class="flex items-end">
                <flux:checkbox wire:model="form.is_active" :label="__('Active')" />
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <flux:button :href="route('admin.groups.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">{{ __('Save') }}</flux:button>
        </div>
    </form>

    @if ($form->group)
        <section class="space-y-4 border-t border-zinc-200 pt-6 dark:border-zinc-700">
            <div class="flex items-center justify-between gap-4">
                <flux:heading size="lg">{{ __('Members') }}</flux:heading>
                <flux:button wire:click="openMemberCreate" variant="primary" icon="plus" size="sm">{{ __('Add member') }}</flux:button>
            </div>

            @if ($this->members->isEmpty())
                <div class="rounded-lg border border-zinc-200 bg-white p-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
                    {{ __('No members yet.') }}
                </div>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.cell>{{ __('Person') }}</flux:table.cell>
                        <flux:table.cell>{{ __('Function') }}</flux:table.cell>
                        <flux:table.cell>{{ __('Started') }}</flux:table.cell>
                        <flux:table.cell>{{ __('Ended') }}</flux:table.cell>
                        <flux:table.cell align="end">&nbsp;</flux:table.cell>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->members as $m)
                            <flux:table.row :key="'gmem-'.$m->id">
                                <flux:table.cell variant="strong">
                                    @if ($m->person)
                                        <a href="{{ route('admin.people.edit', $m->person->id) }}" wire:navigate class="text-accent hover:underline">{{ $m->person->name }}</a>
                                    @else
                                        —
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>{{ $m->function?->name ?? '—' }}</flux:table.cell>
                                <flux:table.cell>{{ $m->started_at?->isoFormat('LL') ?? '—' }}</flux:table.cell>
                                <flux:table.cell>
                                    @if ($m->ended_at)
                                        {{ $m->ended_at->isoFormat('LL') }}
                                    @else
                                        <flux:badge color="emerald">{{ __('Active') }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <div class="inline-flex items-center gap-1">
                                        <flux:tooltip :content="__('Edit')">
                                            <flux:button wire:click="openMemberEdit({{ $m->id }})" size="sm" variant="ghost" icon="pencil-square" />
                                        </flux:tooltip>
                                        @if (! $m->ended_at)
                                            <flux:tooltip :content="__('End today')">
                                                <flux:button wire:click="endMember({{ $m->id }})" wire:confirm="{{ __('End this membership today?') }}" size="sm" variant="ghost" icon="x-circle" />
                                            </flux:tooltip>
                                        @endif
                                        <flux:tooltip :content="__('Delete')">
                                            <flux:button wire:click="deleteMember({{ $m->id }})" wire:confirm="{{ __('Delete this membership?') }}" size="sm" variant="ghost" icon="trash" />
                                        </flux:tooltip>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif

            <flux:modal wire:model.self="showMemberModal" class="md:max-w-lg">
                <form wire:submit="saveMember" class="space-y-4">
                    <flux:heading size="lg">{{ $memberForm->assignment ? __('Edit member') : __('Add member') }}</flux:heading>

                    <flux:select
                        wire:model="memberForm.function_id"
                        variant="listbox"
                        searchable
                        :label="__('Function')"
                        :placeholder="__('Pick a function…')"
                        required
                    >
                        @foreach ($this->eligibleFunctions as $fn)
                            <flux:select.option :value="$fn->id">
                                {{ $fn->name }}
                                @if ($fn->max_holders)
                                    <span class="text-xs text-zinc-500">({{ __('max :n', ['n' => $fn->max_holders]) }})</span>
                                @endif
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model.live.debounce.300ms="personSearch" :label="__('Find a person')" :placeholder="__('Type a name to search…')" />

                    @if ($this->candidatePersons->isNotEmpty())
                        <flux:radio.group wire:model="memberForm.person_id" :label="__('Pick the person')">
                            @foreach ($this->candidatePersons as $candidate)
                                <flux:radio :value="$candidate->id" :label="$candidate->name" />
                            @endforeach
                        </flux:radio.group>
                    @elseif ($personSearch !== '')
                        <flux:text class="text-sm text-zinc-500">{{ __('No matches.') }}</flux:text>
                    @endif

                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:date-picker wire:model="memberForm.started_at" :label="__('Started at')" />
                        <flux:date-picker wire:model="memberForm.ended_at" :label="__('Ended at')" />
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <flux:button type="button" variant="ghost" x-on:click="$wire.showMemberModal = false">{{ __('Cancel') }}</flux:button>
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="saveMember">{{ __('Save') }}</flux:button>
                    </div>
                </form>
            </flux:modal>
        </section>
    @endif
</div>
