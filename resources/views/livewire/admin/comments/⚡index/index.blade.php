<div class="space-y-4">
    <flux:heading size="xl">{{ __('Comment moderation') }}</flux:heading>

    <flux:kanban class="overflow-x-auto pb-2">
        <flux:kanban.column>
            <flux:kanban.column.header :heading="__('Pending')" :count="$this->pendingComments->count()">
                <flux:icon.clock variant="micro" class="text-amber-500" />
            </flux:kanban.column.header>
            <flux:kanban.column.cards>
                @forelse ($this->pendingComments as $comment)
                    <flux:kanban.card wire:key="comment-{{ $comment->id }}">
                        <div class="text-xs text-zinc-500">
                            <span class="font-medium text-zinc-800 dark:text-white">{{ $comment->author?->name }}</span>
                            <span> · {{ $comment->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="mt-2 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-200">{{ $comment->body }}</p>
                        <div class="mt-2 truncate text-xs text-zinc-500">
                            {{ __('On') }}
                            <a class="hover:underline" href="{{ route('posts.show', $comment->post->slug) }}" wire:navigate>{{ $comment->post->title }}</a>
                            @if ($comment->post->church) — {{ $comment->post->church->name }} @endif
                        </div>
                        <div class="mt-3 flex gap-2">
                            <flux:button wire:click="approve({{ $comment->id }})" size="sm" variant="primary">{{ __('Approve') }}</flux:button>
                            <flux:button wire:click="reject({{ $comment->id }})" size="sm" variant="ghost">{{ __('Reject') }}</flux:button>
                        </div>
                    </flux:kanban.card>
                @empty
                    <flux:text class="px-3 py-4 text-center text-xs text-zinc-400">{{ __('No pending comments.') }}</flux:text>
                @endforelse
            </flux:kanban.column.cards>
        </flux:kanban.column>

        <flux:kanban.column>
            <flux:kanban.column.header :heading="__('Approved')" :count="$this->approvedComments->count()">
                <flux:icon.check-circle variant="micro" class="text-emerald-500" />
            </flux:kanban.column.header>
            <flux:kanban.column.cards>
                @forelse ($this->approvedComments as $comment)
                    <flux:kanban.card wire:key="comment-{{ $comment->id }}">
                        <div class="text-xs text-zinc-500">
                            <span class="font-medium text-zinc-800 dark:text-white">{{ $comment->author?->name }}</span>
                            <span> · {{ $comment->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="mt-2 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-200">{{ $comment->body }}</p>
                        <div class="mt-2 truncate text-xs text-zinc-500">
                            {{ __('On') }}
                            <a class="hover:underline" href="{{ route('posts.show', $comment->post->slug) }}" wire:navigate>{{ $comment->post->title }}</a>
                            @if ($comment->post->church) — {{ $comment->post->church->name }} @endif
                        </div>
                        <div class="mt-3 flex gap-2">
                            <flux:button wire:click="reject({{ $comment->id }})" size="sm" variant="ghost">{{ __('Reject') }}</flux:button>
                        </div>
                    </flux:kanban.card>
                @empty
                    <flux:text class="px-3 py-4 text-center text-xs text-zinc-400">{{ __('No approved comments yet.') }}</flux:text>
                @endforelse
            </flux:kanban.column.cards>
        </flux:kanban.column>

        <flux:kanban.column>
            <flux:kanban.column.header :heading="__('Rejected')" :count="$this->rejectedComments->count()">
                <flux:icon.x-circle variant="micro" class="text-rose-500" />
            </flux:kanban.column.header>
            <flux:kanban.column.cards>
                @forelse ($this->rejectedComments as $comment)
                    <flux:kanban.card wire:key="comment-{{ $comment->id }}">
                        <div class="text-xs text-zinc-500">
                            <span class="font-medium text-zinc-800 dark:text-white">{{ $comment->author?->name }}</span>
                            <span> · {{ $comment->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="mt-2 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-200">{{ $comment->body }}</p>
                        <div class="mt-2 truncate text-xs text-zinc-500">
                            {{ __('On') }}
                            <a class="hover:underline" href="{{ route('posts.show', $comment->post->slug) }}" wire:navigate>{{ $comment->post->title }}</a>
                            @if ($comment->post->church) — {{ $comment->post->church->name }} @endif
                        </div>
                        <div class="mt-3 flex gap-2">
                            <flux:button wire:click="approve({{ $comment->id }})" size="sm" variant="primary">{{ __('Approve') }}</flux:button>
                        </div>
                    </flux:kanban.card>
                @empty
                    <flux:text class="px-3 py-4 text-center text-xs text-zinc-400">{{ __('No rejected comments.') }}</flux:text>
                @endforelse
            </flux:kanban.column.cards>
        </flux:kanban.column>
    </flux:kanban>
</div>
