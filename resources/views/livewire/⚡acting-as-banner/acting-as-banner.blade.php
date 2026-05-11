<div>
    @if ($this->actingAsPerson)
        <div class="border-b border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-900/30">
            <div class="container mx-auto flex flex-wrap items-center justify-between gap-3 px-4 py-2 text-sm sm:px-6 lg:px-8">
                <div class="inline-flex items-center gap-2 text-amber-900 dark:text-amber-100">
                    <flux:icon.user class="size-4" />
                    <span>{{ __('Acting on behalf of :name', ['name' => $this->actingAsPerson->name]) }}</span>
                </div>
                <flux:button wire:click="stop" size="sm" variant="ghost" icon="x-mark">
                    {{ __('Stop acting as') }}
                </flux:button>
            </div>
        </div>
    @endif
</div>
