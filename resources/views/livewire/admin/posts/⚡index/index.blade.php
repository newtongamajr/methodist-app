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

    @if ($this->posts->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
            {{ __('No posts yet.') }}
        </div>
    @else
        <flux:table :paginate="$this->posts">
            <flux:table.columns>
                <flux:table.column>{{ __('Title') }}</flux:table.column>
                <flux:table.column>{{ __('Scope') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Author') }}</flux:table.column>
                <flux:table.column align="end">&nbsp;</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->posts as $post)
                    <flux:table.row :key="'post-'.$post->id">
                        <flux:table.cell variant="strong">
                            <a href="{{ route('admin.posts.edit', $post) }}" class="hover:underline" wire:navigate>
                                {{ $post->title }}
                            </a>
                            <div class="text-xs text-zinc-500">{{ $post->updated_at->diffForHumans() }}</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$post->scope->value === 'shared' ? 'sky' : 'zinc'">
                                {{ $post->scope->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="match($post->status->value) { 'published' => 'emerald', 'archived' => 'zinc', default => 'amber' }">
                                {{ $post->status->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $post->author?->name }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:tooltip :content="__('Delete')">
                                <flux:button wire:click="delete({{ $post->id }})" wire:confirm="{{ __('Delete this post?') }}" size="sm" variant="ghost" icon="trash" />
                            </flux:tooltip>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>