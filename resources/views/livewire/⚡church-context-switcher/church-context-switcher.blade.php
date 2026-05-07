<div>
    @php
        $count = $this->churches->count();
        $name = $this->currentName;
    @endphp

    @if ($count > 1)
        <flux:dropdown align="end">
            <flux:button size="sm" variant="ghost" icon="building-library" icon-trailing="chevron-down">
                <span class="max-w-48 truncate">{{ $name ?? __('Pick a church') }}</span>
            </flux:button>
            <flux:menu>
                @foreach ($this->churches as $church)
                    <flux:menu.item
                        wire:click="switchTo({{ $church->id }})"
                        :icon="$church->id === $this->currentId ? 'check' : null"
                    >
                        {{ $church->name }}
                    </flux:menu.item>
                @endforeach
            </flux:menu>
        </flux:dropdown>
    @elseif ($count === 1 && $name)
        <span class="inline-flex items-center gap-1.5 rounded-md bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
            <flux:icon.building-library class="size-3.5" />
            <span class="max-w-40 truncate">{{ $name }}</span>
        </span>
    @endif
</div>