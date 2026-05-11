<div>
    <div class="mb-4 flex items-center justify-between gap-4">
        <flux:heading size="lg">{{ __('Contacts') }}</flux:heading>
        <flux:button wire:click="openCreate" variant="primary" icon="plus" size="sm">{{ __('Add contact') }}</flux:button>
    </div>

    @if ($this->contacts->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
            {{ __('No contacts yet.') }}
        </div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Type') }}</flux:table.column>
                <flux:table.column>{{ __('Value') }}</flux:table.column>
                <flux:table.column>{{ __('Label') }}</flux:table.column>
                <flux:table.column>{{ __('Primary') }}</flux:table.column>
                <flux:table.column align="end">&nbsp;</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->contacts as $contact)
                    <flux:table.row :key="'contact-'.$contact->id">
                        <flux:table.cell><flux:badge color="zinc">{{ $contact->type?->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell variant="strong">{{ $contact->value }}</flux:table.cell>
                        <flux:table.cell>{{ $contact->label ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($contact->is_primary)
                                <flux:badge color="emerald">{{ __('Primary') }}</flux:badge>
                            @else
                                —
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="inline-flex items-center gap-1">
                                <flux:tooltip :content="__('Edit')">
                                    <flux:button wire:click="openEdit({{ $contact->id }})" size="sm" variant="ghost" icon="pencil-square" />
                                </flux:tooltip>
                                <flux:tooltip :content="__('Delete')">
                                    <flux:modal.trigger :name="'delete-contact-'.$contact->id">
                                        <flux:button size="sm" variant="ghost" icon="trash" />
                                    </flux:modal.trigger>
                                </flux:tooltip>
                                <x-confirm-delete
                                    :name="'delete-contact-'.$contact->id"
                                    :heading="__('Delete this contact?')"
                                    action="delete({{ $contact->id }})"
                                />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif

    <flux:modal wire:model.self="showModal" class="md:max-w-lg">
        <form
            wire:submit="save"
            class="space-y-4"
            x-data="{
                phoneTypes: ['phone', 'mobile', 'whatsapp'],
                mobileTypes: ['mobile', 'whatsapp'],
                masks: @js(\App\Enums\Country::cases()
                    ? collect(\App\Enums\Country::cases())->mapWithKeys(fn ($c) => [$c->value => [
                        'fixed' => $c->fixedMask(),
                        'mobile' => $c->mobileMask(),
                    ]])->all()
                    : []),
                isPhone() { return this.phoneTypes.includes($wire.form.type); },
                phoneMask() {
                    const country = $wire.form.country || 'BR';
                    const kind = this.mobileTypes.includes($wire.form.type) ? 'mobile' : 'fixed';
                    return (this.masks[country] && this.masks[country][kind]) || '';
                },
            }"
        >
            <flux:heading size="lg">{{ $form->contact ? __('Edit contact') : __('Add contact') }}</flux:heading>

            <flux:select wire:model.live="form.type" :label="__('Type')" required>
                @foreach (\App\Enums\PersonContactType::cases() as $t)
                    <option value="{{ $t->value }}">{{ $t->label() }}</option>
                @endforeach
            </flux:select>

            <div x-show="isPhone()" x-cloak>
                <flux:select
                    wire:model.live="form.country"
                    variant="listbox"
                    searchable
                    :label="__('Country')"
                    :placeholder="__('Pick a country…')"
                >
                    @foreach (\App\Enums\Country::options() as $iso => $name)
                        <flux:select.option :value="$iso">{{ $name }} (+{{ \App\Enums\Country::from($iso)->phoneCode() }})</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:input
                wire:model="form.value"
                :label="__('Value')"
                required
                x-mask:dynamic="isPhone() ? phoneMask() : ''"
                x-bind:placeholder="isPhone() ? phoneMask() : ''"
            />
            <flux:input wire:model="form.label" :label="__('Label')" :placeholder="__('e.g. Personal, Work')" />
            <flux:checkbox wire:model="form.is_primary" :label="__('Primary contact for this type')" />

            <div class="flex justify-end gap-2 pt-2">
                <flux:button type="button" variant="ghost" x-on:click="$wire.showModal = false">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
