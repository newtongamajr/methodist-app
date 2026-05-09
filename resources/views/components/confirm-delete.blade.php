@props([
    'name',
    'action',
    'heading' => null,
    'message' => null,
    'confirmLabel' => null,
    'cancelLabel' => null,
])

{{-- Reusable delete-confirmation modal. Pair with a `<flux:modal.trigger :name="$same">`
     anywhere on the page to open it. The OK button fires `wire:click="$action"` and
     auto-closes the modal so callers don't have to wire that themselves. --}}
<flux:modal :name="$name" class="md:max-w-md">
    <div class="space-y-6">
        <div class="flex flex-col items-center text-center">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-500/15">
                <flux:icon.x-mark class="h-7 w-7 text-red-600 dark:text-red-400" />
            </div>
            <flux:heading size="lg" class="mt-4">{{ $heading ?? __('Delete') }}</flux:heading>
            @if ($message)
                <flux:text class="mt-2">{{ $message }}</flux:text>
            @endif
        </div>

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">{{ $cancelLabel ?? __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button
                variant="danger"
                wire:click="{{ $action }}"
                x-on:click="$dispatch('modal-close', { name: '{{ $name }}' })"
            >
                {{ $confirmLabel ?? __('OK') }}
            </flux:button>
        </div>
    </div>
</flux:modal>
