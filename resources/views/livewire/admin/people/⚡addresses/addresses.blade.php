<div>
    <div class="mb-4 flex items-center justify-between gap-4">
        <flux:heading size="lg">{{ __('Addresses') }}</flux:heading>
        <flux:button wire:click="openCreate" variant="primary" icon="plus" size="sm">{{ __('Add address') }}</flux:button>
    </div>

    @if ($this->addresses->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
            {{ __('No addresses yet.') }}
        </div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Label') }}</flux:table.column>
                <flux:table.column>{{ __('Address') }}</flux:table.column>
                <flux:table.column>{{ __('City') }}</flux:table.column>
                <flux:table.column>{{ __('Primary') }}</flux:table.column>
                <flux:table.column align="end">&nbsp;</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->addresses as $address)
                    <flux:table.row :key="'address-'.$address->id">
                        <flux:table.cell variant="strong">{{ $address->label ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            {{ $address->street }}@if ($address->number), {{ $address->number }}@endif@if ($address->complement), {{ $address->complement }}@endif
                            @if ($address->neighborhood)
                                <span class="text-xs text-zinc-500"> · {{ $address->neighborhood }}</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $address->city ?? '—' }}@if ($address->state)/{{ $address->state }}@endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($address->is_primary)
                                <flux:badge color="emerald">{{ __('Primary') }}</flux:badge>
                            @else
                                —
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="inline-flex items-center gap-1">
                                <flux:tooltip :content="__('Edit')">
                                    <flux:button wire:click="openEdit({{ $address->id }})" size="sm" variant="ghost" icon="pencil-square" />
                                </flux:tooltip>
                                <flux:tooltip :content="__('Delete')">
                                    <flux:modal.trigger :name="'delete-address-'.$address->id">
                                        <flux:button size="sm" variant="ghost" icon="trash" />
                                    </flux:modal.trigger>
                                </flux:tooltip>
                                <x-confirm-delete
                                    :name="'delete-address-'.$address->id"
                                    :heading="__('Delete this address?')"
                                    action="delete({{ $address->id }})"
                                />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif

    <flux:modal wire:model.self="showModal" class="md:max-w-2xl">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $form->address ? __('Edit address') : __('Add address') }}</flux:heading>

            <flux:input wire:model="form.label" :label="__('Label')" :placeholder="__('e.g. Home, Work')" />

            <div class="grid gap-4 sm:grid-cols-5">
                <div class="sm:col-span-4">
                    <flux:input wire:model="form.street" :label="__('Street')" />
                </div>
                <flux:input wire:model="form.number" :label="__('Number')" />
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="form.complement" :label="__('Complement')" />
                <div class="sm:col-span-2">
                    <flux:input wire:model="form.neighborhood" :label="__('Neighborhood')" />
                </div>
            </div>

            <div
                x-data="{
                    zipMasks: @js(collect(\App\Enums\Country::cases())->mapWithKeys(fn ($c) => [$c->value => $c->zipMask()])->all()),
                    isForeign() { return $wire.form.state === '{{ \App\Enums\BrazilianState::Foreign->value }}'; },
                    zipMask() { return this.zipMasks[$wire.form.country] ?? ''; },
                }"
            >
                <div class="grid gap-4 sm:grid-cols-4">
                    <div class="sm:col-span-3">
                        <flux:input wire:model="form.city" :label="__('City')" />
                    </div>
                    <flux:select wire:model.live="form.state" :label="__('State')">
                        <option value="">{{ __('— None —') }}</option>
                        @foreach (\App\Enums\BrazilianState::options() as $uf => $name)
                            <option value="{{ $uf }}">{{ $uf }} — {{ $name }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <flux:input
                        wire:model="form.zip"
                        :label="__('ZIP')"
                        x-mask:dynamic="zipMask()"
                        x-bind:placeholder="zipMask()"
                    />
                    <div x-show="isForeign()" x-cloak>
                        <flux:select
                            wire:model.live="form.country"
                            variant="listbox"
                            searchable
                            :label="__('Country')"
                            :placeholder="__('Pick a country…')"
                        >
                            {{-- Keep every country including BR so the listbox can always
                                 resolve its current value. The state↔country coupling is
                                 enforced server-side in PersonAddressForm::save(). --}}
                            @foreach (\App\Enums\Country::options() as $iso => $name)
                                <flux:select.option :value="$iso">{{ $name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div x-show="!isForeign()" x-cloak>
                        <flux:input :value="__('Brazil')" :label="__('Country')" disabled />
                    </div>
                </div>
            </div>
            <flux:checkbox wire:model="form.is_primary" :label="__('Primary address')" />

            <div class="flex justify-end gap-2 pt-2">
                <flux:button type="button" variant="ghost" x-on:click="$wire.showModal = false">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
