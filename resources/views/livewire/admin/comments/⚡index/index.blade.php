<div class="space-y-6">
    <flux:heading size="xl">{{ __('Comment moderation') }}</flux:heading>

    <flux:select wire:model.live="statusFilter">
        <option value="">{{ __('All statuses') }}</option>
        @foreach ($statuses as $s)
            <option value="{{ $s->value }}">{{ $s->label() }}</option>
        @endforeach
    </flux:select>

    <div class="space-y-3">
        @forelse ($this->comments as $comment)
            <div wire:key="comment-{{ $comment->id }}" class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                    <div>
                        <span class="font-medium">{{ $comment->author?->name }}</span>
                        <span class="text-zinc-500"> · {{ $comment->created_at->diffForHumans() }}</span>
                    </div>
                    <flux:badge :color="match($comment->status->value) { 'approved' => 'emerald', 'rejected' => 'rose', default => 'amber' }">
                        {{ $comment->status->label() }}
                    </flux:badge>
                </div>

                <p class="mt-2 whitespace-pre-line text-zinc-700 dark:text-zinc-200">{{ $comment->body }}</p>

                <div class="mt-3 flex items-center justify-between gap-2 text-xs text-zinc-500">
                    <span>{{ __('On') }}
                        <a class="text-[#c8202f] hover:underline dark:text-rose-300" href="{{ route('posts.show', $comment->post->slug) }}" wire:navigate>{{ $comment->post->title }}</a>
                        @if ($comment->post->church)
                            — {{ $comment->post->church->name }}
                        @endif
                    </span>

                    <div class="flex gap-2">
                        @if ($comment->status->value !== 'approved')
                            <flux:button wire:click="approve({{ $comment->id }})" size="sm" variant="primary">{{ __('Approve') }}</flux:button>
                        @endif
                        @if ($comment->status->value !== 'rejected')
                            <flux:button wire:click="reject({{ $comment->id }})" size="sm" variant="ghost">{{ __('Reject') }}</flux:button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <flux:text class="text-zinc-500">{{ __('No comments to review.') }}</flux:text>
        @endforelse
    </div>

    <div>{{ $this->comments->links() }}</div>
</div>