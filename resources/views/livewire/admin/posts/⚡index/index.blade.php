<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('Posts') }}</flux:heading>
        <flux:button :href="route('admin.posts.create')" variant="primary" icon="plus" wire:navigate>
            {{ __('New post') }}
        </flux:button>
    </div>

    <div class="space-y-3">
        <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search by title…')" icon="magnifying-glass" />

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <flux:select wire:model.live="statusFilter">
                <option value="">{{ __('All statuses') }}</option>
                @foreach ($statuses as $s)
                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="audienceFilter">
                <option value="">{{ __('All audiences') }}</option>
                @foreach ($audiences as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="authorFilter">
                <option value="">{{ __('All authors') }}</option>
                @foreach ($this->availableAuthors as $author)
                    <option value="{{ $author->id }}">{{ $author->name }}</option>
                @endforeach
            </flux:select>
        </div>
    </div>

    @if ($this->posts->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
            {{ __('No posts yet.') }}
        </div>
    @else
        <flux:table :paginate="$this->posts">
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'title'" :direction="$sortDir" wire:click="sort('title')">
                    {{ __('Title') }}
                </flux:table.column>
                <flux:table.column>{{ __('Audience') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'author'" :direction="$sortDir" wire:click="sort('author')">
                    {{ __('Author') }}
                </flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'published_at'" :direction="$sortDir" wire:click="sort('published_at')">
                    {{ __('Published at') }}
                </flux:table.column>
                <flux:table.column align="end">&nbsp;</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->posts as $post)
                    <flux:table.row :key="'post-'.$post->id">
                        <flux:table.cell variant="strong">
                            <div class="flex items-center gap-3">
                                @php($thumb = $post->coverUrl('thumb'))
                                @if ($thumb)
                                    <img
                                        src="{{ $thumb }}"
                                        alt=""
                                        class="aspect-video w-32 shrink-0 rounded-md object-cover ring-1 ring-zinc-200 dark:ring-zinc-700"
                                    />
                                @else
                                    <div
                                        class="flex aspect-video w-32 shrink-0 items-center justify-center rounded-md bg-zinc-100 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700"
                                        aria-hidden="true"
                                    >
                                        <flux:icon.photo class="size-6 text-zinc-400" />
                                    </div>
                                @endif
                                <span class="min-w-0 break-words">{{ $post->title }}</span>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{-- Shape badges per scope row, deduped by shape
                                 so a post hitting 5 churches shows one
                                 "Local" tag instead of five. Inline @php()
                                 form survives Flux's Blaze slot compilation
                                 reliably, where the multi-statement block
                                 sometimes drops the variables. --}}
                            @php($shapes = $post->scopes->map(fn ($s) => $s->shape())->unique()->values())
                            <div class="flex flex-wrap gap-1">
                                @foreach ($shapes as $shape)
                                    @php($shapeColor = match ($shape) { 'national' => 'sky', 'regional' => 'indigo', 'district' => 'amber', default => 'zinc' })
                                    @php($shapeLabel = match ($shape) { 'national' => __('National'), 'regional' => __('Regional'), 'district' => __('District'), default => __('Local') })
                                    <flux:badge size="sm" :color="$shapeColor">{{ $shapeLabel }}</flux:badge>
                                @endforeach
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="match($post->status->value) { 'published' => 'emerald', 'archived' => 'zinc', default => 'amber' }">
                                {{ $post->status->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $post->author?->name }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($post->published_at)
                                {{ $post->published_at->isoFormat('lll') }}
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="inline-flex items-center gap-1">
                                <flux:tooltip :content="__('Edit')">
                                    <flux:button
                                        :href="route('admin.posts.edit', $post)"
                                        wire:navigate
                                        size="sm"
                                        variant="ghost"
                                        icon="pencil-square"
                                    />
                                </flux:tooltip>
                                <flux:tooltip :content="__('Delete')">
                                    <flux:modal.trigger :name="'delete-post-'.$post->id">
                                        <flux:button size="sm" variant="ghost" icon="trash" />
                                    </flux:modal.trigger>
                                </flux:tooltip>
                                <x-confirm-delete
                                    :name="'delete-post-'.$post->id"
                                    :heading="__('Delete this post?')"
                                    action="delete({{ $post->id }})"
                                />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
