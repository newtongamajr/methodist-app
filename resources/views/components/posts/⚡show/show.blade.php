<div class="mx-auto max-w-3xl space-y-8 px-4 py-10 sm:px-6">
    <article class="space-y-6">
        @if ($coverUrl = $post->coverUrl('hero'))
            <img src="{{ $coverUrl }}" alt="" class="h-64 w-full rounded-lg object-cover">
        @endif

        <div class="flex flex-wrap items-center gap-2 text-sm text-zinc-500">
            <span>{{ $post->published_at?->isoFormat('LL') }}</span>
            <span aria-hidden="true">·</span>
            <span>{{ $post->author?->name }}</span>
            @if ($post->scope->value === 'local' && $post->church)
                <span aria-hidden="true">·</span>
                <flux:badge color="zinc">{{ $post->church->name }}</flux:badge>
            @else
                <flux:badge color="sky">{{ __('Shared') }}</flux:badge>
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
            <section
                x-data="{ open: false, src: '' }"
                @keydown.escape.window="open = false"
                class="space-y-3"
            >
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ($images as $image)
                        <button
                            type="button"
                            wire:key="img-{{ $image->id }}"
                            @click.prevent="open = true; src = '{{ $image->getUrl() }}'"
                            class="block overflow-hidden rounded-md focus:outline-none focus:ring-2 focus:ring-[#c8202f]"
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

                <div
                    x-cloak
                    x-show="open"
                    x-transition.opacity
                    class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-900/85 p-4"
                    @click.self="open = false"
                >
                    <button
                        type="button"
                        class="absolute right-4 top-4 rounded-full bg-white/10 p-2 text-white hover:bg-white/20"
                        @click="open = false"
                        aria-label="{{ __('Close') }}"
                    >
                        <flux:icon.x-mark class="size-5" />
                    </button>
                    <img :src="src" alt="" class="max-h-[90vh] max-w-full rounded-lg shadow-2xl">
                </div>
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
                                    <img src="{{ $embed->thumbnail_url }}" alt="" class="h-16 w-24 flex-none rounded object-cover">
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
            <section class="space-y-2" x-data="{ open: false, src: '' }" @keydown.escape.window="open = false">
                @foreach ($documents as $doc)
                    <button
                        type="button"
                        wire:key="doc-{{ $doc->id }}"
                        @click.prevent="open = true; src = '{{ $doc->getUrl() }}'"
                        class="flex w-full items-center gap-3 rounded-lg border border-zinc-200 p-3 text-left hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800"
                    >
                        <flux:icon.document-text class="size-6 text-zinc-500" />
                        <span class="flex-1 truncate font-medium">{{ $doc->name }}</span>
                        <span class="text-xs text-zinc-500">{{ $doc->human_readable_size }}</span>
                    </button>
                @endforeach

                <div
                    x-cloak
                    x-show="open"
                    x-transition.opacity
                    class="fixed inset-0 z-50 flex flex-col bg-zinc-900/95"
                >
                    <div class="flex items-center justify-between p-3">
                        <span class="text-sm text-zinc-200">{{ __('Document viewer') }}</span>
                        <button
                            type="button"
                            class="rounded-full bg-white/10 p-2 text-white hover:bg-white/20"
                            @click="open = false; src = ''"
                            aria-label="{{ __('Close') }}"
                        >
                            <flux:icon.x-mark class="size-5" />
                        </button>
                    </div>
                    <iframe :src="src" class="flex-1 bg-white" loading="lazy" title="{{ __('Document viewer') }}"></iframe>
                </div>
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
                <div class="rounded-md bg-emerald-50 p-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                    {{ session('comment-status') }}
                </div>
            @endif

            <form wire:submit="submitComment" class="space-y-3">
                <flux:textarea wire:model="newComment" rows="3" :placeholder="__('Share an encouraging word…')" />
                @error('newComment') <flux:text class="text-rose-600">{{ $message }}</flux:text> @enderror

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">{{ __('Send comment') }}</flux:button>
                </div>
            </form>
        @else
            <flux:text>
                <a href="{{ route('login') }}" class="font-medium text-[#c8202f] hover:underline" wire:navigate>{{ __('Sign in') }}</a>
                {{ __('to like and comment.') }}
            </flux:text>
        @endauth

        <div class="space-y-4 pt-4">
            @forelse ($this->approvedComments as $comment)
                <div wire:key="comment-{{ $comment->id }}" class="rounded-md bg-zinc-50 p-4 dark:bg-zinc-800/60">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium">{{ $comment->author?->name }}</span>
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