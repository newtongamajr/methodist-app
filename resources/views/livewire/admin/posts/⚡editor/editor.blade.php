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

        <flux:field>
            <flux:label>{{ __('Body') }}</flux:label>
            <div wire:ignore>
                <textarea
                    data-tinymce="rich"
                    data-livewire-prop="form.body"
                    id="post-body-{{ $form->post?->id ?? 'new' }}"
                    class="mt-2"
                >{!! $form->body !!}</textarea>
            </div>
            <flux:error name="form.body" />
        </flux:field>

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:select wire:model="form.status" :label="__('Status')">
                @foreach (\App\Enums\PostStatus::cases() as $s)
                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                @endforeach
            </flux:select>

            <flux:input wire:model="form.published_at" :label="__('Publish at')" type="datetime-local" />
        </div>

        {{-- ─── Audience picker ─────────────────────────────────────── --}}
        <flux:fieldset>
            <flux:legend>{{ __('Audience') }}</flux:legend>
            <flux:description>
                {{ __('Pick where this post should appear. You can target multiple regions, districts, and churches at once.') }}
            </flux:description>

            <div class="mt-3 space-y-4">
                @if ($this->canPublishNational())
                    <flux:checkbox
                        wire:model="form.national_post"
                        :label="__('National — visible to every user')"
                    />
                @endif

                @if ($this->availableRegions->isNotEmpty())
                    <flux:pillbox
                        wire:model="form.region_ids"
                        :label="__('Regions')"
                        :placeholder="__('Pick one or more regions…')"
                        multiple
                        searchable
                        clearable
                    >
                        @foreach ($this->availableRegions as $r)
                            <flux:pillbox.option :value="$r->id" :label="$r->code.' — '.$r->name" />
                        @endforeach
                    </flux:pillbox>
                @endif

                @if ($this->availableDistricts->isNotEmpty())
                    <flux:pillbox
                        wire:model="form.district_ids"
                        :label="__('Districts')"
                        :placeholder="__('Pick one or more districts…')"
                        multiple
                        searchable
                        clearable
                    >
                        @foreach ($this->availableDistricts as $d)
                            <flux:pillbox.option :value="$d->id" :label="$d->name" />
                        @endforeach
                    </flux:pillbox>
                @endif

                @if ($this->availableChurches->isNotEmpty())
                    <flux:pillbox
                        wire:model="form.church_ids"
                        :label="__('Churches')"
                        :placeholder="__('Pick one or more churches…')"
                        multiple
                        searchable
                        clearable
                    >
                        @foreach ($this->availableChurches as $c)
                            <flux:pillbox.option
                                :value="$c->id"
                                :label="$c->name.($c->city ? ' — '.$c->city.'/'.$c->state : '')"
                            />
                        @endforeach
                    </flux:pillbox>
                @endif
            </div>

            <flux:error name="form.scopes" />
        </flux:fieldset>

        {{-- ─── Cover image (cropper) ─────────────────────────────────── --}}
        <section
            class="space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700"
            x-data="imageCropper({
                modal: 'post-cover-cropper',
                wireProperty: 'newCover',
                wireSave: '',
                aspectRatio: 16 / 9,
                outputName: 'cover.png',
            })"
        >
            <flux:heading size="md">{{ __('Cover image') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500">{{ __('A single landscape image used at the top of the post. Crop to a 16:9 frame.') }}</flux:text>

            <div class="flex flex-wrap items-center gap-4">
                <div class="shrink-0">
                    @if ($newCover)
                        {{-- Pending pick — show the temp uploaded preview so
                             the user can tell their crop landed before saving. --}}
                        <img src="{{ $newCover->temporaryUrl() }}" alt="" class="h-32 rounded-md object-cover" />
                    @elseif ($coverUrl)
                        <img src="{{ $coverUrl }}" alt="" class="h-32 rounded-md object-cover" />
                    @else
                        <div class="flex h-32 w-56 items-center justify-center rounded-md border border-dashed border-zinc-300 text-xs text-zinc-400 dark:border-zinc-600">
                            {{ __('No cover yet') }}
                        </div>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <input
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        class="hidden"
                        x-ref="fileInput"
                        x-on:change="pickFile($event)"
                    />
                    <flux:button
                        type="button"
                        variant="primary"
                        icon="arrow-up-tray"
                        x-on:click="$refs.fileInput.click()"
                    >
                        {{ __('Choose cover') }}
                    </flux:button>

                    @if ($coverUrl && ! $newCover)
                        <flux:button type="button" variant="danger" icon="trash" wire:click="removeCover">
                            {{ __('Remove cover') }}
                        </flux:button>
                    @endif

                    <flux:text class="text-xs text-zinc-500">
                        {{ __('JPG, PNG or WebP up to 10 MB. Click Save on the post to persist.') }}
                    </flux:text>
                </div>
            </div>

            <flux:error name="newCover" />

            <flux:modal name="post-cover-cropper" class="md:w-3xl" @close="close()">
                <div class="space-y-4">
                    <div>
                        <flux:heading size="lg">{{ __('Crop the cover') }}</flux:heading>
                        <flux:text class="mt-1">{{ __('Frame and rotate the image at a 16:9 ratio.') }}</flux:text>
                    </div>

                    <div class="relative mx-auto max-h-[60vh] overflow-hidden bg-zinc-100 dark:bg-zinc-900" wire:ignore>
                        <img data-crop-image alt="" class="block max-w-full" />
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <flux:button type="button" size="sm" icon="magnifying-glass-plus" x-on:click="zoom(0.1)">{{ __('Zoom in') }}</flux:button>
                        <flux:button type="button" size="sm" icon="magnifying-glass-minus" x-on:click="zoom(-0.1)">{{ __('Zoom out') }}</flux:button>
                        <flux:button type="button" size="sm" icon="arrow-uturn-left" x-on:click="rotate(-90)">{{ __('Rotate −90°') }}</flux:button>
                        <flux:button type="button" size="sm" icon="arrow-uturn-right" x-on:click="rotate(90)">{{ __('Rotate +90°') }}</flux:button>
                        <flux:button type="button" size="sm" icon="arrows-right-left" x-on:click="flipH()">{{ __('Flip horizontal') }}</flux:button>
                        <flux:button type="button" size="sm" icon="arrows-up-down" x-on:click="flipV()">{{ __('Flip vertical') }}</flux:button>
                    </div>

                    <div class="flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                        </flux:modal.close>
                        <flux:button type="button" variant="primary" icon="check" x-on:click="save()" x-bind:disabled="saving">
                            <span x-show="! saving">{{ __('Use this crop') }}</span>
                            <span x-show="saving" x-cloak>{{ __('Uploading…') }}</span>
                        </flux:button>
                    </div>
                </div>
            </flux:modal>
        </section>

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
            <flux:error name="newImages.*" />

            {{-- Pending uploads: rendered before persist so the user
                 sees the pick land. Cleared once Save runs. --}}
            @if (! empty($newImages))
                <div class="space-y-2 rounded-md border border-dashed border-amber-300 bg-amber-50 p-3 dark:border-amber-700 dark:bg-amber-900/20">
                    <flux:text class="text-xs font-medium text-amber-700 dark:text-amber-200">
                        {{ __('Pending — click Save to attach :count file(s)', ['count' => count($newImages)]) }}
                    </flux:text>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        @foreach ($newImages as $i => $upload)
                            <div wire:key="pending-img-{{ $i }}-{{ $upload?->getFilename() }}" class="group relative">
                                <img src="{{ $upload->temporaryUrl() }}" alt="" class="h-24 w-full rounded-md object-cover" />
                                <flux:tooltip :content="__('Remove')">
                                    <flux:button
                                        type="button"
                                        variant="danger"
                                        icon="x-mark"
                                        size="sm"
                                        class="absolute right-1 top-1 opacity-0 group-hover:opacity-100"
                                        wire:click="removePending('newImages', {{ $i }})"
                                    />
                                </flux:tooltip>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading size="md">{{ __('Videos') }}</flux:heading>
            @if ($videos->isNotEmpty())
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ($videos as $media)
                        @php
                            $thumbUrl = $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : null;
                        @endphp
                        <div wire:key="vid-{{ $media->id }}" class="group relative overflow-hidden rounded-md border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                            <a href="{{ $media->getUrl() }}" target="_blank" rel="noopener" class="block">
                                <div class="relative flex aspect-video items-center justify-center overflow-hidden bg-gradient-to-br from-zinc-100 to-zinc-200 dark:from-zinc-800 dark:to-zinc-900">
                                    @if ($thumbUrl)
                                        <img src="{{ $thumbUrl }}" alt="" loading="lazy" class="h-full w-full object-cover" />
                                    @else
                                        <flux:icon.film class="size-12 text-zinc-400 dark:text-zinc-500" />
                                    @endif
                                    {{-- Play-button overlay so the tile reads
                                         as a video regardless of whether the
                                         thumb came through. --}}
                                    <span class="absolute inset-0 flex items-center justify-center bg-black/0 transition group-hover:bg-black/30">
                                        <flux:icon.play-circle class="size-10 text-white/90 opacity-0 transition group-hover:opacity-100" />
                                    </span>
                                </div>
                                <div class="space-y-0.5 border-t border-zinc-200 px-2 py-1.5 text-xs dark:border-zinc-700">
                                    <div class="truncate font-medium">{{ $media->file_name }}</div>
                                    <div class="text-zinc-500">{{ $media->human_readable_size }}</div>
                                </div>
                            </a>
                            <flux:tooltip :content="__('Remove video')">
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
            <flux:file-upload wire:model="newVideos" accept="video/mp4,video/webm,video/ogg,video/quicktime" multiple>
                <flux:file-upload.dropzone
                    inline
                    icon="film"
                    :heading="__('Drop videos or click to upload')"
                    :text="__('MP4, WebM, OGG or MOV, up to 100 MB each')"
                />
            </flux:file-upload>
            <flux:error name="newVideos.*" />

            @if (! empty($newVideos))
                <ul class="divide-y divide-amber-200 rounded-md border border-dashed border-amber-300 bg-amber-50 dark:divide-amber-700 dark:border-amber-700 dark:bg-amber-900/20">
                    <li class="px-3 py-2 text-xs font-medium text-amber-700 dark:text-amber-200">
                        {{ __('Pending — click Save to attach :count file(s)', ['count' => count($newVideos)]) }}
                    </li>
                    @foreach ($newVideos as $i => $upload)
                        <li wire:key="pending-vid-{{ $i }}-{{ $upload?->getFilename() }}" class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                            <div class="flex min-w-0 items-center gap-2">
                                <flux:icon.film class="size-4 shrink-0 text-amber-700 dark:text-amber-300" />
                                <span class="truncate">{{ $upload->getClientOriginalName() }}</span>
                            </div>
                            <flux:button type="button" variant="ghost" icon="x-mark" size="sm" wire:click="removePending('newVideos', {{ $i }})" />
                        </li>
                    @endforeach
                </ul>
            @endif
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
            <flux:error name="newAudios.*" />

            @if (! empty($newAudios))
                <ul class="divide-y divide-amber-200 rounded-md border border-dashed border-amber-300 bg-amber-50 dark:divide-amber-700 dark:border-amber-700 dark:bg-amber-900/20">
                    <li class="px-3 py-2 text-xs font-medium text-amber-700 dark:text-amber-200">
                        {{ __('Pending — click Save to attach :count file(s)', ['count' => count($newAudios)]) }}
                    </li>
                    @foreach ($newAudios as $i => $upload)
                        <li wire:key="pending-aud-{{ $i }}-{{ $upload?->getFilename() }}" class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                            <div class="flex min-w-0 items-center gap-2">
                                <flux:icon.musical-note class="size-4 shrink-0 text-amber-700 dark:text-amber-300" />
                                <span class="truncate">{{ $upload->getClientOriginalName() }}</span>
                            </div>
                            <flux:button type="button" variant="ghost" icon="x-mark" size="sm" wire:click="removePending('newAudios', {{ $i }})" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading size="md">{{ __('PDF documents') }}</flux:heading>
            @if ($documents->isNotEmpty())
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($documents as $media)
                        @php
                            $thumbUrl = $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : null;
                        @endphp
                        <div wire:key="doc-{{ $media->id }}" class="group relative overflow-hidden rounded-md border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                            <a href="{{ $media->getUrl() }}" target="_blank" rel="noopener" class="block">
                                <div class="flex aspect-[3/4] items-center justify-center overflow-hidden bg-gradient-to-br from-zinc-50 to-zinc-100 dark:from-zinc-800 dark:to-zinc-900">
                                    @if ($thumbUrl)
                                        <img src="{{ $thumbUrl }}" alt="" loading="lazy" class="h-full w-full object-contain" />
                                    @else
                                        <flux:icon.document-text class="size-12 text-zinc-400 dark:text-zinc-500" />
                                    @endif
                                </div>
                                <div class="space-y-0.5 border-t border-zinc-200 px-2 py-1.5 text-xs dark:border-zinc-700">
                                    <div class="truncate font-medium">{{ $media->file_name }}</div>
                                    <div class="text-zinc-500">{{ $media->human_readable_size }}</div>
                                </div>
                            </a>
                            <flux:tooltip :content="__('Remove document')">
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
            <flux:file-upload wire:model="newDocuments" accept="application/pdf" multiple>
                <flux:file-upload.dropzone
                    inline
                    icon="document"
                    :heading="__('Drop PDFs or click to upload')"
                    :text="__('PDF only, up to 20 MB each')"
                />
            </flux:file-upload>
            <flux:error name="newDocuments.*" />

            @if (! empty($newDocuments))
                <ul class="divide-y divide-amber-200 rounded-md border border-dashed border-amber-300 bg-amber-50 dark:divide-amber-700 dark:border-amber-700 dark:bg-amber-900/20">
                    <li class="px-3 py-2 text-xs font-medium text-amber-700 dark:text-amber-200">
                        {{ __('Pending — click Save to attach :count file(s)', ['count' => count($newDocuments)]) }}
                    </li>
                    @foreach ($newDocuments as $i => $upload)
                        <li wire:key="pending-doc-{{ $i }}-{{ $upload?->getFilename() }}" class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                            <div class="flex min-w-0 items-center gap-2">
                                <flux:icon.document class="size-4 shrink-0 text-amber-700 dark:text-amber-300" />
                                <span class="truncate">{{ $upload->getClientOriginalName() }}</span>
                            </div>
                            <flux:button type="button" variant="ghost" icon="x-mark" size="sm" wire:click="removePending('newDocuments', {{ $i }})" />
                        </li>
                    @endforeach
                </ul>
            @endif
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
            <flux:error name="newEmbedUrl" />
        </div>

        <div class="flex justify-end gap-2">
            <flux:button :href="route('admin.posts.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save" x-on:click="window.tinymceFlushAll && window.tinymceFlushAll()">
                {{ __('Save') }}
            </flux:button>
        </div>
    </form>
</div>