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
        <div x-data="{ src: '' }">
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
                        @php
                            $media = $document->getFirstMedia('image');
                            $mime = $media?->mime_type;
                            $isImage = $mime && str_starts_with($mime, 'image/');
                            $isPdf = $mime === 'application/pdf';
                            $url = $media?->getUrl();
                        @endphp
                        <flux:table.row :key="'doc-'.$document->id">
                            <flux:table.cell variant="strong">{{ $document->document_type }}</flux:table.cell>
                            <flux:table.cell class="font-mono text-xs">{{ $document->number ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $document->issuer ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $document->issued_at?->isoFormat('LL') ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $document->expires_at?->isoFormat('LL') ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                @if ($url && $isImage)
                                    <flux:tooltip :content="__('Preview image')">
                                        <button
                                            type="button"
                                            @click.prevent="src = '{{ $url }}'; $dispatch('modal-show', { name: 'doc-image-viewer' })"
                                            class="inline-flex items-center gap-1 text-accent hover:underline"
                                        >
                                            <flux:icon.photo class="size-4" />
                                            {{ __('Open') }}
                                        </button>
                                    </flux:tooltip>
                                @elseif ($url && $isPdf)
                                    <flux:tooltip :content="__('Preview PDF')">
                                        <button
                                            type="button"
                                            @click.prevent="src = '{{ $url }}'; $dispatch('modal-show', { name: 'doc-pdf-viewer' })"
                                            class="inline-flex items-center gap-1 text-accent hover:underline"
                                        >
                                            <flux:icon.document-text class="size-4" />
                                            {{ __('Open') }}
                                        </button>
                                    </flux:tooltip>
                                @elseif ($url)
                                    {{-- Fallback for an unknown MIME: open in a new tab. --}}
                                    <a href="{{ $url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-accent hover:underline">
                                        <flux:icon.arrow-top-right-on-square class="size-4" />
                                        {{ __('Open') }}
                                    </a>
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

            {{-- Image lightbox: same pattern as posts/show.blade.php. --}}
            <flux:modal name="doc-image-viewer" variant="bare">
                <img :src="src" alt="" class="max-h-[90vh] max-w-full rounded-lg shadow-2xl">
            </flux:modal>

            {{-- PDF viewer: iframe-in-modal, same pattern as posts/show.blade.php. --}}
            <flux:modal name="doc-pdf-viewer" class="md:max-w-5xl">
                <div class="space-y-3">
                    <flux:heading size="md">{{ __('Document viewer') }}</flux:heading>
                    <iframe
                        :src="src"
                        class="h-[80vh] w-full rounded-md bg-white"
                        loading="lazy"
                        title="{{ __('Document viewer') }}"
                    ></iframe>
                </div>
            </flux:modal>
        </div>
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
