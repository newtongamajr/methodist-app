<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('Posts') }}</flux:heading>
        <flux:button :href="route('admin.posts.create')" variant="primary" icon="plus" wire:navigate>
            {{ __('New post') }}
        </flux:button>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search by title…')" icon="magnifying-glass" />

        <flux:select wire:model.live="statusFilter">
            <option value="">{{ __('All statuses') }}</option>
            @foreach ($statuses as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="scopeFilter">
            <option value="">{{ __('All scopes') }}</option>
            @foreach ($scopes as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </flux:select>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700 text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('Title') }}</th>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('Scope') }}</th>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('Status') }}</th>
                    <th class="px-4 py-2 text-start font-semibold">{{ __('Author') }}</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($this->posts as $post)
                    <tr wire:key="post-{{ $post->id }}">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.posts.edit', $post) }}" class="font-medium text-[#c8202f] hover:underline dark:text-rose-300" wire:navigate>
                                {{ $post->title }}
                            </a>
                            <div class="text-xs text-zinc-500">{{ $post->updated_at->diffForHumans() }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge :color="$post->scope->value === 'shared' ? 'sky' : 'zinc'">
                                {{ $post->scope->label() }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge :color="match($post->status->value) { 'published' => 'emerald', 'archived' => 'zinc', default => 'amber' }">
                                {{ $post->status->label() }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3">{{ $post->author?->name }}</td>
                        <td class="px-4 py-3 text-end">
                            <flux:button wire:click="delete({{ $post->id }})" wire:confirm="{{ __('Delete this post?') }}" size="sm" variant="ghost" icon="trash" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-zinc-500">{{ __('No posts yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $this->posts->links() }}</div>
</div>