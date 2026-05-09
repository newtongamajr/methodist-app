<div>
    <div class="mb-4 flex items-center justify-between gap-4">
        <flux:heading size="lg">{{ __('Documents') }}</flux:heading>
        <flux:button wire:click="openCreate" variant="primary" icon="plus" size="sm">{{ __('Add document') }}</flux:button>
    </div>

    @if ($this->documents->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
            {{ __('No documents yet.') }}
        </div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Type') }}</flux:table.column>
                <flux:table.column>{{ __('Number') }}</flux:table.column>
                <flux:table.column>{{ __('Issuer') }}</flux:table.column>
                <flux:table.column>{{ __('Issued') }}</flux:table.column>
                <flux:table.column>{{ __('Expires') }}</flux:table.column>
                <flux:table.column>{{ __('File') }}</flux:table.column>
                <flux:table.column align="end">&nbsp;</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->documents as $document)
                    <flux:table.row :key="'doc-'.$document->id">
                        <flux:table.cell variant="strong">{{ $document->document_type }}</flux:table.cell>
                        <flux:table.cell class="font-mono text-xs">{{ $document->number ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $document->issuer ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $document->issued_at?->isoFormat('LL') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $document->expires_at?->isoFormat('LL') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            @php $url = $document->getFirstMediaUrl('image'); @endphp
                            @if ($url)
                                <a href="{{ $url }}" target="_blank" class="text-accent hover:underline">{{ __('Open') }}</a>
                            @else
                                —
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="inline-flex items-center gap-1">
                                <flux:tooltip :content="__('Edit')">
                                    <flux:button wire:click="openEdit({{ $document->id }})" size="sm" variant="ghost" icon="pencil-square" />
                                </flux:tooltip>
                                <flux:tooltip :content="__('Delete')">
                                    <flux:button wire:click="delete({{ $document->id }})" wire:confirm="{{ __('Delete this document?') }}" size="sm" variant="ghost" icon="trash" />
                                </flux:tooltip>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif

    <flux:modal wire:model.self="showModal" class="md:max-w-2xl">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $form->document ? __('Edit document') : __('Add document') }}</flux:heading>

            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="form.document_type" :label="__('Type')" :placeholder="__('e.g. RG, CNH')" required />
                <flux:input wire:model="form.number" :label="__('Number')" class="sm:col-span-2" />
            </div>

            <flux:input wire:model="form.issuer" :label="__('Issuer')" />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:date-picker wire:model="form.issued_at" :label="__('Issued at')" />
                <flux:date-picker wire:model="form.expires_at" :label="__('Expires at')" />
            </div>

            <flux:field>
                <flux:label>{{ __('Scanned image / PDF') }}</flux:label>
                <flux:description>{{ __('JPEG, PNG, WebP or PDF, up to 10 MB.') }}</flux:description>
                <input type="file" wire:model="newImage" accept="image/jpeg,image/png,image/webp,application/pdf" class="block w-full text-sm">
                <flux:error name="newImage" />
            </flux:field>

            <div class="flex justify-end gap-2 pt-2">
                <flux:button type="button" variant="ghost" x-on:click="$wire.showModal = false">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
