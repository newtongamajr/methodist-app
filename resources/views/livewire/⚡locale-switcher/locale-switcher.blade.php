<div>
    <flux:dropdown align="end">
        <flux:button size="sm" variant="ghost" icon-trailing="chevron-down">
            {{ collect($this->options)->firstWhere('value', $this->current)['short'] ?? 'PT' }}
        </flux:button>

        <flux:menu>
            @foreach ($this->options as $option)
                <flux:menu.item
                    wire:click="switchTo('{{ $option['value'] }}')"
                    :icon="$option['value'] === $this->current ? 'check' : null"
                >
                    {{ $option['label'] }}
                </flux:menu.item>
            @endforeach
        </flux:menu>
    </flux:dropdown>
</div>