<div>
    @php
        $currentIcon = collect($this->options)->firstWhere('value', $this->current)['icon'] ?? 'computer-desktop';
    @endphp

    <flux:dropdown align="end">
        <flux:button size="sm" variant="ghost" :icon="$currentIcon" square aria-label="{{ __('Theme') }}" />

        <flux:menu>
            @foreach ($this->options as $option)
                <flux:menu.item
                    x-on:click="
                        window.applyAppearance('{{ $option['value'] }}');
                        $wire.switchTo('{{ $option['value'] }}');
                    "
                    :icon="$option['icon']"
                >
                    {{ $option['label'] }}
                    @if ($option['value'] === $this->current)
                        <flux:icon.check class="ms-auto size-4" />
                    @endif
                </flux:menu.item>
            @endforeach
        </flux:menu>
    </flux:dropdown>
</div>
