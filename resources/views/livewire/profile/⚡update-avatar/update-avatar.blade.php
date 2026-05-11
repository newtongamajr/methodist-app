<section x-data="avatarCropper" x-on:avatar-updated.window="$wire.$refresh()">
    <header>
        <flux:heading size="lg">{{ __('Profile picture') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Upload an image, then crop and rotate it before saving.') }}</flux:text>
    </header>

    <div class="mt-6 flex items-center gap-6">
        <div class="shrink-0">
            @if ($avatarUrl)
                <img
                    src="{{ $avatarUrl }}"
                    alt="{{ auth()->user()->name }}"
                    class="size-24 rounded-full object-cover ring-2 ring-zinc-200 dark:ring-zinc-700"
                />
            @else
                <div class="flex size-24 items-center justify-center rounded-full bg-zinc-100 text-2xl font-semibold text-zinc-500 ring-2 ring-zinc-200 dark:bg-zinc-700 dark:text-zinc-300 dark:ring-zinc-700">
                    {{ \Illuminate\Support\Str::of(auth()->user()->name)->substr(0, 1)->upper() }}
                </div>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <input
                type="file"
                accept="image/jpeg,image/png,image/webp"
                class="hidden"
                x-ref="fileInput"
                x-on:change="pickFile($event)"
            />

            <flux:button
                type="button"
                variant="primary"
                icon="arrow-up-tray"
                x-on:click="$refs.fileInput.click()"
            >
                {{ __('Choose image') }}
            </flux:button>

            @if ($avatarUrl)
                <flux:button
                    type="button"
                    variant="danger"
                    icon="trash"
                    wire:click="removeAvatar"
                    wire:confirm="{{ __('Remove your profile picture?') }}"
                >
                    {{ __('Remove') }}
                </flux:button>
            @endif

            <x-action-message class="text-sm text-emerald-600 dark:text-emerald-400" on="avatar-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </div>

    @error('newAvatar')
        <flux:text class="mt-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
    @enderror

    <flux:modal name="avatar-cropper" class="md:w-2xl" @close="close()">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Crop your avatar') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Frame and rotate the image, then save.') }}</flux:text>
            </div>

            <div class="relative mx-auto max-h-[60vh] overflow-hidden bg-zinc-100 dark:bg-zinc-900" wire:ignore>
                <img x-ref="cropImage" alt="" class="block max-w-full" />
            </div>

            <div class="flex flex-wrap gap-2">
                <flux:button type="button" size="sm" icon="magnifying-glass-plus" x-on:click="zoom(0.1)">{{ __('Zoom in') }}</flux:button>
                <flux:button type="button" size="sm" icon="magnifying-glass-minus" x-on:click="zoom(-0.1)">{{ __('Zoom out') }}</flux:button>
                <flux:button type="button" size="sm" icon="arrow-uturn-left" x-on:click="rotate(-90)">{{ __('Rotate −90°') }}</flux:button>
                <flux:button type="button" size="sm" icon="arrow-uturn-right" x-on:click="rotate(90)">{{ __('Rotate +90°') }}</flux:button>
                <flux:button type="button" size="sm" icon="arrows-right-left" x-on:click="flipH()">{{ __('Flip horizontal') }}</flux:button>
                <flux:button type="button" size="sm" icon="arrows-up-down" x-on:click="flipV()">{{ __('Flip vertical') }}</flux:button>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="button" variant="primary" icon="check" x-on:click="save()" x-bind:disabled="saving">
                    <span x-show="! saving">{{ __('Save avatar') }}</span>
                    <span x-show="saving" x-cloak>{{ __('Saving…') }}</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>