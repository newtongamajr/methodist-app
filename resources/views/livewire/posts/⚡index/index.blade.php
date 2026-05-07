<div class="mx-auto max-w-3xl space-y-6 px-4 py-10 sm:px-6">
    <flux:heading size="xl">{{ __('Posts') }}</flux:heading>

    <div class="space-y-4">
        @forelse ($this->posts as $post)
            <article wire:key="post-{{ $post->id }}" class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                @if ($coverUrl = $post->coverUrl('card'))
                    <img src="{{ $coverUrl }}" alt="" class="mb-4 h-48 w-full rounded-md object-cover">
                @endif

                <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-500">
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

                <h2 class="mt-2 text-2xl font-semibold">
                    <a href="{{ route('posts.show', $post->slug) }}" class="hover:underline" wire:navigate>
                        {{ $post->title }}
                    </a>
                </h2>

                @if ($post->excerpt)
                    <div class="prose prose-sm prose-zinc mt-2 max-w-none text-zinc-600 dark:prose-invert dark:text-zinc-300">
                        {!! $post->excerpt !!}
                    </div>
                @endif

                <div class="mt-4 flex items-center gap-4 text-sm text-zinc-500">
                    <span class="inline-flex items-center gap-1">
                        <flux:icon.heart class="size-4" /> {{ $post->likes_count }}
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <flux:icon.chat-bubble-left class="size-4" /> {{ $post->comments_count }}
                    </span>
                </div>
            </article>
        @empty
            <flux:text class="text-center text-zinc-500">{{ __('No posts published yet.') }}</flux:text>
        @endforelse
    </div>

    <div>{{ $this->posts->links() }}</div>
</div>