<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('admin.posts.index')" wire:navigate>{{ __('Posts') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $form->post ? __('Edit post') : __('New post') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">
            {{ $form->post ? __('Edit post') : __('New post') }}
        </flux:heading>
        <flux:button :href="route('admin.posts.index')" variant="ghost" wire:navigate>{{ __('Back to list') }}</flux:button>
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle" inline :heading="session('status')" />
    @endif

    <form wire:submit="save" class="space-y-6">
        <flux:input wire:model="form.title" :label="__('Title')" required />

        <flux:field>
            <flux:label>{{ __('Excerpt') }}</flux:label>
            <flux:editor wire:model="form.excerpt" toolbar="bold italic underline | bullet ordered | link" />
            <flux:error name="form.excerpt" />
        </flux:field>

        <div>
            <flux:label>{{ __('Body') }}</flux:label>
            <div wire:ignore>
                <textarea
                    data-tinymce="rich"
                    data-livewire-prop="form.body"
                    id="post-body-{{ $form->post?->id ?? 'new' }}"
                    class="mt-2"
                >{!! $form->body !!}</textarea>
            </div>
            @error('form.body') <flux:text class="mt-1 text-rose-600">{{ $message }}</flux:text> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <flux:select wire:model.live="form.scope" :label="__('Scope')">
                @foreach (\App\Enums\PostScope::cases() as $s)
                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model="form.status" :label="__('Status')">
                @foreach (\App\Enums\PostStatus::cases() as $s)
                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                @endforeach
            </flux:select>

            <flux:input wire:model="form.published_at" :label="__('Publish at')" type="datetime-local" />
        </div>

        @if ($form->scope === 'local')
            <flux:select wire:model="form.church_id" :label="__('Church')">
                <option value="">{{ __('— Select a church —') }}</option>
                @foreach ($this->churches as $church)
                    <option value="{{ $church->id }}">
                        {{ $church->name }}@if ($church->city) — {{ $church->city }}/{{ $church->state }}@endif
                    </option>
                @endforeach
            </flux:select>
        @endif

        <div class="space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading size="md">{{ __('Cover image') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500">{{ __('A single landscape image used at the top of the post.') }}</flux:text>

            @if ($coverUrl && ! $newCover)
                <div class="flex items-center gap-3">
                    <img src="{{ $coverUrl }}" alt="" class="h-32 rounded-md object-cover">
                    <flux:button type="button" variant="danger" icon="trash" size="sm" wire:click="removeCover">
                        {{ __('Remove cover') }}
                    </flux:button>
                </div>
            @endif

            <flux:file-upload wire:model="newCover" accept="image/jpeg,image/png,image/webp">
                <flux:file-upload.dropzone
                    inline
                    icon="photo"
                    :heading="__('Drop a cover image or click to upload')"
                    :text="__('JPG, PNG or WebP up to 10 MB')"
                />
            </flux:file-upload>
            @error('newCover') <flux:text class="text-rose-600">{{ $message }}</flux:text> @enderror
        </div>

        <div class="space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading size="md">{{ __('Images') }}</flux:heading>
            @if ($images->isNotEmpty())
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ($images as $media)
                        <div wire:key="img-{{ $media->id }}" class="group relative">
                            <img src="{{ $media->getUrl('thumb') ?: $media->getUrl() }}" alt="" class="h-28 w-full rounded-md object-cover">
                            <flux:tooltip :content="__('Remove image')">
                                <flux:button
                                    type="button"
                                    variant="danger"
                                    icon="trash"
                                    size="sm"
                                    class="absolute right-1 top-1 opacity-0 group-hover:opacity-100"
                                    wire:click="removeMedia({{ $media->id }})"
                                />
                            </flux:tooltip>
                        </div>
                    @endforeach
                </div>
            @endif
            <flux:file-upload wire:model="newImages" accept="image/*" multiple>
                <flux:file-upload.dropzone
                    inline
                    icon="photo"
                    :heading="__('Drop images or click to upload')"
                    :text="__('Multiple JPG, PNG, WebP or GIF, up to 10 MB each')"
                />
            </flux:file-upload>
            @error('newImages.*') <flux:text class="text-rose-600">{{ $message }}</flux:text> @enderror
        </div>

        <div class="space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading size="md">{{ __('Videos') }}</flux:heading>
            @if ($videos->isNotEmpty())
                <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($videos as $media)
                        <li wire:key="vid-{{ $media->id }}" class="flex items-center justify-between py-2 text-sm">
                            <div class="flex items-center gap-2">
                                <flux:icon.film class="size-4 text-zinc-500" />
                                <span>{{ $media->file_name }}</span>
                                <span class="text-xs text-zinc-500">({{ $media->human_readable_size }})</span>
                            </div>
                            <flux:tooltip :content="__('Remove video')">
                                <flux:button type="button" variant="danger" icon="trash" size="sm" wire:click="removeMedia({{ $media->id }})" />
                            </flux:tooltip>
                        </li>
                    @endforeach
                </ul>
            @endif
            <flux:file-upload wire:model="newVideos" accept="video/mp4,video/webm,video/ogg,video/quicktime" multiple>
                <flux:file-upload.dropzone
                    inline
                    icon="film"
                    :heading="__('Drop videos or click to upload')"
                    :text="__('MP4, WebM, OGG or MOV, up to 100 MB each')"
                />
            </flux:file-upload>
            @error('newVideos.*') <flux:text class="text-rose-600">{{ $message }}</flux:text> @enderror
        </div>

        <div class="space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading size="md">{{ __('Audio') }}</flux:heading>
            @if ($audios->isNotEmpty())
                <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($audios as $media)
                        <li wire:key="aud-{{ $media->id }}" class="flex items-center justify-between py-2 text-sm">
                            <div class="flex items-center gap-2">
                                <flux:icon.musical-note class="size-4 text-zinc-500" />
                                <span>{{ $media->file_name }}</span>
                                <span class="text-xs text-zinc-500">({{ $media->human_readable_size }})</span>
                            </div>
                            <flux:tooltip :content="__('Remove audio')">
                                <flux:button type="button" variant="danger" icon="trash" size="sm" wire:click="removeMedia({{ $media->id }})" />
                            </flux:tooltip>
                        </li>
                    @endforeach
                </ul>
            @endif
            <flux:file-upload wire:model="newAudios" accept="audio/*" multiple>
                <flux:file-upload.dropzone
                    inline
                    icon="musical-note"
                    :heading="__('Drop audio files or click to upload')"
                    :text="__('MP3, M4A, OGG, WAV or WebM, up to 50 MB each')"
                />
            </flux:file-upload>
            @error('newAudios.*') <flux:text class="text-rose-600">{{ $message }}</flux:text> @enderror
        </div>

        <div class="space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading size="md">{{ __('PDF documents') }}</flux:heading>
            @if ($documents->isNotEmpty())
                <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($documents as $media)
                        <li wire:key="doc-{{ $media->id }}" class="flex items-center justify-between py-2 text-sm">
                            <div class="flex items-center gap-2">
                                <flux:icon.document class="size-4 text-zinc-500" />
                                <a href="{{ $media->getUrl() }}" target="_blank" rel="noopener" class="hover:underline">{{ $media->file_name }}</a>
                                <span class="text-xs text-zinc-500">({{ $media->human_readable_size }})</span>
                            </div>
                            <flux:tooltip :content="__('Remove document')">
                                <flux:button type="button" variant="danger" icon="trash" size="sm" wire:click="removeMedia({{ $media->id }})" />
                            </flux:tooltip>
                        </li>
                    @endforeach
                </ul>
            @endif
            <flux:file-upload wire:model="newDocuments" accept="application/pdf" multiple>
                <flux:file-upload.dropzone
                    inline
                    icon="document"
                    :heading="__('Drop PDFs or click to upload')"
                    :text="__('PDF only, up to 20 MB each')"
                />
            </flux:file-upload>
            @error('newDocuments.*') <flux:text class="text-rose-600">{{ $message }}</flux:text> @enderror
        </div>

        <div class="space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading size="md">{{ __('Embeds') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500">{{ __('Paste a YouTube, Spotify or Vimeo link. Title and thumbnail are fetched automatically.') }}</flux:text>

            @if (session('embed-status'))
                <flux:callout variant="warning" icon="information-circle" inline :heading="session('embed-status')" />
            @endif

            @if ($embeds->isNotEmpty())
                <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($embeds as $embed)
                        <li wire:key="embed-{{ $embed->id }}" class="flex items-center gap-3 py-2 text-sm">
                            @if ($embed->thumbnail_url)
                                <img src="{{ $embed->thumbnail_url }}" alt="" class="h-12 w-20 flex-none rounded-sm object-cover">
                            @else
                                <div class="flex h-12 w-20 flex-none items-center justify-center rounded-sm bg-zinc-100 text-xs text-zinc-500 dark:bg-zinc-700">
                                    {{ $embed->provider->label() }}
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="truncate font-medium">{{ $embed->title ?? $embed->url }}</div>
                                <div class="truncate text-xs text-zinc-500">{{ $embed->provider->label() }} — {{ $embed->url }}</div>
                            </div>
                            <flux:tooltip :content="__('Remove embed')">
                                <flux:button type="button" variant="danger" icon="trash" size="sm" wire:click="removeEmbed({{ $embed->id }})" />
                            </flux:tooltip>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="flex flex-col gap-2 sm:flex-row">
                <flux:input
                    wire:model="newEmbedUrl"
                    type="url"
                    placeholder="https://www.youtube.com/watch?v=…"
                    class="flex-1"
                />
                <flux:button type="button" variant="primary" icon="plus" wire:click="addEmbed">
                    {{ __('Add embed') }}
                </flux:button>
            </div>
            <div wire:loading wire:target="addEmbed" class="text-xs text-zinc-500">{{ __('Fetching link metadata…') }}</div>
            @error('newEmbedUrl') <flux:text class="text-rose-600">{{ $message }}</flux:text> @enderror
        </div>

        <div class="flex justify-end gap-2">
            <flux:button :href="route('admin.posts.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save" x-on:click="window.tinymceFlushAll && window.tinymceFlushAll()">
                {{ __('Save') }}
            </flux:button>
        </div>
    </form>
</div>