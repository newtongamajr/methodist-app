<div class="mx-auto max-w-3xl space-y-8 px-4 py-10 sm:px-6">
    <flux:heading size="xl">{{ __('Posts') }}</flux:heading>

    <div class="space-y-6">
        @forelse ($this->posts as $post)
            @php
                // Collapse multi-scope posts to their broadest audience tier
                // (national > regional > district > local) so the chip stays
                // a single, compact pill.
                $shapes = $post->scopes->map(fn ($s) => $s->shape())->unique();
                $audience = $shapes->contains('national') ? 'national'
                    : ($shapes->contains('regional') ? 'regional'
                        : ($shapes->contains('district') ? 'district' : 'local'));
                $audienceColor = match ($audience) {
                    'national' => 'sky',
                    'regional' => 'indigo',
                    'district' => 'amber',
                    default => 'zinc',
                };
                $audienceLabel = match ($audience) {
                    'national' => __('National'),
                    'regional' => __('Regional'),
                    'district' => __('District'),
                    default => __('Local'),
                };
            @endphp

            <article
                wire:key="post-{{ $post->id }}"
                class="group relative overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-accent hover:shadow-lg dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-rose-400"
            >
                {{-- Cover spans the full card width on top, with a subtle
                     accent gradient peeking from the side that ties the
                     audience color into the card. --}}
                @if ($coverUrl = $post->coverUrl('card'))
                    <a href="{{ route('posts.show', $post->slug) }}" wire:navigate class="block">
                        {{-- 16:9 to match the cropper aspect ratio so faces
                             aren't lopped off by a fixed-height re-crop. --}}
                        <img src="{{ $coverUrl }}" alt="" class="aspect-video w-full object-cover transition duration-300 group-hover:scale-[1.02]">
                    </a>
                @endif

                <div class="space-y-3 p-6">
                    <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                        <flux:badge size="sm" :color="$audienceColor">{{ $audienceLabel }}</flux:badge>
                        <span aria-hidden="true">·</span>
                        <span>{{ $post->published_at?->isoFormat('LL') }}</span>
                        <span aria-hidden="true">·</span>
                        <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $post->author?->name }}</span>
                    </div>

                    <h2 class="text-2xl font-semibold leading-snug text-zinc-900 transition group-hover:text-accent dark:text-zinc-50 dark:group-hover:text-rose-300">
                        <a href="{{ route('posts.show', $post->slug) }}" wire:navigate class="focus:outline-none focus-visible:underline">
                            {{ $post->title }}
                        </a>
                    </h2>

                    @if ($post->excerpt)
                        <div class="prose prose-sm prose-zinc max-w-none text-zinc-600 dark:prose-invert dark:text-zinc-300">
                            {!! $post->excerpt !!}
                        </div>
                    @endif

                    <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                        {{-- Stats: outlined heart + chat bubble in their own
                             colors, so the eye picks them up against either
                             theme without fighting the body copy. --}}
                        <div class="flex items-center gap-4 text-sm">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 font-medium text-rose-600 ring-1 ring-rose-100 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/20">
                                <flux:icon.heart class="size-4" />
                                {{ $post->likes_count }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-2.5 py-1 font-medium text-sky-600 ring-1 ring-sky-100 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/20">
                                <flux:icon.chat-bubble-left class="size-4" />
                                {{ $post->comments_count }}
                            </span>
                        </div>

                        <a
                            href="{{ route('posts.show', $post->slug) }}"
                            wire:navigate
                            class="inline-flex items-center gap-1 text-sm font-semibold text-accent transition hover:gap-2 dark:text-rose-300"
                        >
                            {{ __('Read the whole story') }}
                            <flux:icon.arrow-right class="size-4" />
                        </a>
                    </div>
                </div>
            </article>
        @empty
            <flux:text class="text-center text-zinc-500">{{ __('No posts published yet.') }}</flux:text>
        @endforelse
    </div>

    <div>{{ $this->posts->links() }}</div>
</div>
