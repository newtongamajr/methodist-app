<div>
    <div class="mb-4 flex items-center justify-between gap-4">
        <flux:heading size="lg">{{ __('Family') }}</flux:heading>
        <flux:button wire:click="openCreate" variant="primary" icon="plus" size="sm">{{ __('Add relationship') }}</flux:button>
    </div>

    @if ($this->relationships->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
            {{ __('No family relationships yet.') }}
        </div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Relationship') }}</flux:table.column>
                <flux:table.column>{{ __('Person') }}</flux:table.column>
                <flux:table.column>{{ __('Started') }}</flux:table.column>
                <flux:table.column>{{ __('Ended') }}</flux:table.column>
                <flux:table.column align="end">&nbsp;</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->relationships as $r)
                    <flux:table.row :key="'rel-'.$r['id'].'-'.($r['editable'] ? 'f' : 'r')">
                        <flux:table.cell variant="strong">{{ $r['type']?->label() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($r['other'])
                                <a href="{{ route('admin.people.edit', $r['other']->id) }}" wire:navigate class="text-accent hover:underline">
                                    {{ $r['other']->name }}
                                </a>
                            @else
                                —
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $r['started_at']?->isoFormat('LL') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($r['ended_at'])
                                {{ $r['ended_at']->isoFormat('LL') }}
                            @else
                                <flux:badge color="emerald">{{ __('Active') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="inline-flex items-center gap-1">
                                @if ($r['editable'])
                                    <flux:tooltip :content="__('Edit')">
                                        <flux:button wire:click="openEdit({{ $r['id'] }})" size="sm" variant="ghost" icon="pencil-square" />
                                    </flux:tooltip>
                                    <flux:tooltip :content="__('Delete')">
                                        <flux:modal.trigger :name="'delete-relationship-'.$r['id']">
                                            <flux:button size="sm" variant="ghost" icon="trash" />
                                        </flux:modal.trigger>
                                    </flux:tooltip>
                                    <x-confirm-delete
                                        :name="'delete-relationship-'.$r['id']"
                                        :heading="__('Delete this relationship?')"
                                        action="delete({{ $r['id'] }})"
                                    />
                                @else
                                    <flux:tooltip :content="__('Defined from the other person — edit there')">
                                        <flux:icon.information-circle class="size-4 text-zinc-400" />
                                    </flux:tooltip>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif

    <flux:modal wire:model.self="showModal" class="md:max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $form->relationship ? __('Edit relationship') : __('Add relationship') }}</flux:heading>

            <flux:select wire:model="form.relationship_type" :label="__('Relationship type')" required>
                @foreach (\App\Enums\PersonRelationshipType::cases() as $t)
                    <option value="{{ $t->value }}">{{ $t->label() }}</option>
                @endforeach
            </flux:select>

            <flux:input wire:model.live.debounce.300ms="personSearch" :label="__('Find a person')" :placeholder="__('Type a name to search…')" />

            @if ($this->candidatePersons->isNotEmpty())
                <flux:radio.group wire:model="form.related_person_id" :label="__('Pick the related person')">
                    @foreach ($this->candidatePersons as $candidate)
                        <flux:radio :value="$candidate->id" :label="$candidate->name" />
                    @endforeach
                </flux:radio.group>
            @elseif ($personSearch !== '')
                <flux:text class="text-sm text-zinc-500">{{ __('No matches.') }}</flux:text>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:date-picker wire:model="form.started_at" :label="__('Started at')" />
                <flux:date-picker wire:model="form.ended_at" :label="__('Ended at')" />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:button type="button" variant="ghost" x-on:click="$wire.showModal = false">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
