<div class="mx-auto max-w-3xl space-y-6 px-4 py-10 sm:px-6">
    <flux:button
        :href="route('posts.index')"
        wire:navigate
        variant="ghost"
        icon="arrow-left"
        size="sm"
    >
        {{ __('Back to posts') }}
    </flux:button>

    <article class="space-y-6">
        @if ($coverUrl = $post->coverUrl('hero'))
            {{-- Display the cover at the same 16:9 ratio it was cropped at
                 in the editor — using a fixed height + object-cover crops
                 the already-cropped image again and cuts faces. --}}
            <img src="{{ $coverUrl }}" alt="" class="aspect-video w-full rounded-lg object-cover">
        @endif

        <div class="flex flex-wrap items-center gap-2 text-sm text-zinc-500">
            <span>{{ $post->published_at?->isoFormat('LL') }}</span>
            <span aria-hidden="true">·</span>
            <span>{{ $post->author?->name }}</span>
            @php
                $shapes = $post->scopes->map(fn ($s) => $s->shape())->unique();
                $audience = $shapes->contains('national') ? 'national'
                    : ($shapes->contains('regional') ? 'regional'
                        : ($shapes->contains('district') ? 'district' : 'local'));
            @endphp
            @if ($audience === 'national')
                <flux:badge color="sky">{{ __('National') }}</flux:badge>
            @elseif ($audience === 'regional')
                <flux:badge color="indigo">{{ __('Regional') }}</flux:badge>
            @elseif ($audience === 'district')
                <flux:badge color="amber">{{ __('District') }}</flux:badge>
            @else
                <flux:badge color="zinc">{{ __('Local') }}</flux:badge>
            @endif
        </div>

        <flux:heading size="xl">{{ $post->title }}</flux:heading>

        <div class="prose prose-zinc max-w-none dark:prose-invert">
            {!! $post->body !!}
        </div>

        @php
            $images = $post->getMedia('images');
            $videos = $post->getMedia('videos');
            $audios = $post->getMedia('audios');
            $documents = $post->getMedia('documents');
        @endphp

        @if ($images->isNotEmpty())
            <section x-data="{ src: '' }" class="space-y-3">
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ($images as $image)
                        <button
                            type="button"
                            wire:key="img-{{ $image->id }}"
                            @click.prevent="src = '{{ $image->getUrl() }}'; $dispatch('modal-show', { name: 'lightbox' })"
                            class="block overflow-hidden rounded-md focus:outline-hidden focus:ring-2 focus:ring-accent"
                        >
                            <img
                                src="{{ $image->getUrl('card') ?: $image->getUrl() }}"
                                alt="{{ $image->name }}"
                                loading="lazy"
                                class="aspect-video w-full object-cover transition hover:scale-105"
                            >
                        </button>
                    @endforeach
                </div>

                <flux:modal name="lightbox" variant="bare">
                    <img :src="src" alt="" class="max-h-[90vh] max-w-full rounded-lg shadow-2xl">
                </flux:modal>
            </section>
        @endif

        @foreach ($videos as $video)
            <div wire:key="vid-{{ $video->id }}" wire:ignore>
                <video data-plyr playsinline controls preload="metadata" class="w-full rounded-lg">
                    <source src="{{ $video->getUrl() }}" type="{{ $video->mime_type }}">
                </video>
            </div>
        @endforeach

        @foreach ($audios as $audio)
            <div wire:key="aud-{{ $audio->id }}" wire:ignore class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800/60">
                <div class="mb-2 text-sm font-medium">{{ $audio->name }}</div>
                <audio data-plyr controls preload="metadata" class="w-full">
                    <source src="{{ $audio->getUrl() }}" type="{{ $audio->mime_type }}">
                </audio>
            </div>
        @endforeach

        @if ($post->embeds->isNotEmpty())
            <section class="space-y-4">
                @foreach ($post->embeds as $embed)
                    <div wire:key="embed-{{ $embed->id }}" wire:ignore>
                        @if ($embed->provider === \App\Enums\EmbedProvider::YouTube && $ytId = $embed->youtubeId())
                            <div data-plyr-provider="youtube" data-plyr-embed-id="{{ $ytId }}" data-plyr></div>
                        @elseif ($embed->provider === \App\Enums\EmbedProvider::Vimeo && $vmId = $embed->vimeoId())
                            <div data-plyr-provider="vimeo" data-plyr-embed-id="{{ $vmId }}" data-plyr></div>
                        @elseif ($embed->provider === \App\Enums\EmbedProvider::Spotify && $spUrl = $embed->spotifyEmbedUrl())
                            <iframe
                                src="{{ $spUrl }}"
                                width="100%"
                                height="152"
                                frameborder="0"
                                allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"
                                loading="lazy"
                                class="rounded-xl"
                            ></iframe>
                        @else
                            <a
                                href="{{ $embed->url }}"
                                target="_blank"
                                rel="noopener"
                                class="flex items-center gap-3 rounded-lg border border-zinc-200 p-3 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800"
                            >
                                @if ($embed->thumbnail_url)
                                    <img src="{{ $embed->thumbnail_url }}" alt="" class="h-16 w-24 flex-none rounded-sm object-cover">
                                @endif
                                <div class="min-w-0 flex-1">
                                    <div class="truncate font-medium">{{ $embed->title ?? $embed->url }}</div>
                                    <div class="truncate text-xs text-zinc-500">{{ $embed->provider->label() }}</div>
                                </div>
                                <flux:icon.arrow-top-right-on-square class="size-4 text-zinc-400" />
                            </a>
                        @endif
                    </div>
                @endforeach
            </section>
        @endif

        @if ($documents->isNotEmpty())
            <section x-data="{ src: '' }">
                {{-- Card grid: each PDF gets a tile with a prominent
                     document-icon "thumb" and the filename + size below.
                     Click anywhere on the tile to open the full viewer. --}}
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($documents as $doc)
                        @php
                            // Inline @php() with a ?: ternary trips Blade's
                            // naive paren matcher (it stops compiling the
                            // rest of the loop). Use the block form so the
                            // assignment compiles to plain PHP.
                            $thumbUrl = $doc->hasGeneratedConversion('thumb') ? $doc->getUrl('thumb') : null;
                        @endphp
                        <button
                            type="button"
                            wire:key="doc-{{ $doc->id }}"
                            @click.prevent="src = '{{ $doc->getUrl() }}'; $dispatch('modal-show', { name: 'doc-viewer' })"
                            class="group flex flex-col overflow-hidden rounded-lg border border-zinc-200 bg-white text-left shadow-sm transition hover:-translate-y-0.5 hover:border-accent hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-rose-400"
                        >
                            <div class="flex aspect-[3/4] items-center justify-center overflow-hidden bg-gradient-to-br from-zinc-50 to-zinc-100 dark:from-zinc-800 dark:to-zinc-900">
                                @if ($thumbUrl)
                                    {{-- First-page preview rendered by Spatie's
                                         PDF image generator (imagick + gs). --}}
                                    <img src="{{ $thumbUrl }}" alt="" loading="lazy" class="h-full w-full object-contain transition group-hover:scale-[1.02]" />
                                @else
                                    {{-- Fallback when the thumb conversion
                                         hasn't been generated (e.g. imagick
                                         not installed on this host). --}}
                                    <flux:icon.document-text class="size-16 text-zinc-400 transition group-hover:text-accent dark:text-zinc-500 dark:group-hover:text-rose-300" />
                                @endif
                            </div>
                            <div class="space-y-1 border-t border-zinc-200 px-3 py-2 dark:border-zinc-700">
                                <div class="truncate text-sm font-medium">{{ $doc->name }}</div>
                                <div class="flex items-center justify-between text-xs text-zinc-500">
                                    <span>{{ $doc->human_readable_size }}</span>
                                    <span class="inline-flex items-center gap-1 font-semibold text-accent transition group-hover:gap-2 dark:text-rose-300">
                                        {{ __('Open') }}
                                        <flux:icon.arrow-right class="size-3" />
                                    </span>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>

                {{-- The viewer modal expands to nearly the full viewport
                     so a PDF page reads comfortably without the 5xl cap. --}}
                <flux:modal name="doc-viewer" class="w-[95vw] max-w-[1400px]!">
                    <div class="space-y-3">
                        <flux:heading size="md">{{ __('Document viewer') }}</flux:heading>
                        <iframe
                            :src="src"
                            class="h-[85vh] w-full rounded-md bg-white"
                            loading="lazy"
                            title="{{ __('Document viewer') }}"
                        ></iframe>
                    </div>
                </flux:modal>
            </section>
        @endif

        <div class="flex items-center gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
            <flux:button
                wire:click="toggleLike"
                variant="{{ $this->liked ? 'primary' : 'ghost' }}"
                icon="heart"
                :icon:variant="$this->liked ? 'solid' : 'outline'"
            >
                {{ $this->likesCount }} {{ trans_choice('like|likes', $this->likesCount) }}
            </flux:button>
            <flux:text class="text-sm text-zinc-500">
                {{ trans_choice(':count comment|:count comments', $this->approvedComments->count()) }}
            </flux:text>
        </div>
    </article>

    <section class="space-y-4">
        <flux:heading size="lg">{{ __('Comments') }}</flux:heading>

        @auth
            @if (session('comment-status'))
                <flux:callout variant="success" icon="check-circle" inline :heading="session('comment-status')" />
            @endif

            <form wire:submit="submitComment" class="space-y-3">
                <flux:field>
                    <flux:textarea wire:model="newComment" rows="3" :placeholder="__('Share an encouraging word…')" />
                    <flux:error name="newComment" />
                </flux:field>

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="submitComment">{{ __('Send comment') }}</flux:button>
                </div>
            </form>
        @else
            <flux:text>
                <a href="{{ route('login') }}" class="font-medium text-accent hover:underline" wire:navigate>{{ __('Sign in') }}</a>
                {{ __('to like and comment.') }}
            </flux:text>
        @endauth

        <div class="space-y-4 pt-4">
            @forelse ($this->approvedComments as $comment)
                @php
                    // When the recording user differs from the participant
                    // Person (a parent commenting on behalf of a child), show
                    // BOTH names so the audience sees the act-as attribution.
                    $authorName = $comment->author?->name;
                    $personName = $comment->person?->name;
                    $isProxied = $personName && $authorName && $comment->person_id !== $comment->author?->person_id;
                @endphp
                <div wire:key="comment-{{ $comment->id }}" class="rounded-md bg-zinc-50 p-4 dark:bg-zinc-800/60">
                    <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                        @if ($isProxied)
                            {{-- Proxy attribution: actor → participant. The
                                 small arrow keeps "<actor> in the name of
                                 <participant>" readable without the verbose
                                 sentence. --}}
                            <span class="inline-flex items-center gap-1.5 font-medium">
                                <span>{{ $authorName }}</span>
                                <flux:icon.arrow-right variant="micro" class="size-3.5 text-zinc-400" />
                                <span>{{ $personName }}</span>
                            </span>
                        @else
                            <span class="font-medium">{{ $personName ?? $authorName }}</span>
                        @endif
                        <span class="text-zinc-500">{{ $comment->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="mt-2 whitespace-pre-line text-zinc-700 dark:text-zinc-200">{{ $comment->body }}</p>
                </div>
            @empty
                <flux:text class="text-zinc-500">{{ __('Be the first to comment.') }}</flux:text>
            @endforelse
        </div>
    </section>
</div>