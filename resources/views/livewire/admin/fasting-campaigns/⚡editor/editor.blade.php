<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">
            {{ $campaign ? __('Edit campaign') : __('New campaign') }}
        </flux:heading>
        <flux:button :href="route('admin.fasting-campaigns.index')" variant="ghost" wire:navigate>{{ __('Back to list') }}</flux:button>
    </div>

    @if (session('status'))
        <div class="rounded-md bg-emerald-50 p-3 text-sm font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-5">
        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="name" :label="__('Name')" required />
            <flux:input wire:model="slug" :label="__('Slug')" :placeholder="__('Leave blank to auto-generate')" />
        </div>

        <flux:textarea wire:model="description" :label="__('Description')" rows="2" />

        <div class="grid gap-4 sm:grid-cols-3">
            <flux:input wire:model="start_date" :label="__('Start date')" type="date" required />
            <flux:input wire:model="end_date" :label="__('End date')" type="date" required />
            <flux:checkbox wire:model="is_active" :label="__('Active')" />
        </div>

        <section class="space-y-3">
            <flux:label>{{ __('Allowed fasting types') }}</flux:label>
            <div class="grid grid-cols-2 gap-2">
                @foreach (\App\Enums\FastingType::cases() as $t)
                    <label class="flex items-center gap-2 rounded-md border border-zinc-200 p-2 text-sm dark:border-zinc-700">
                        <input type="checkbox" value="{{ $t->value }}" wire:model="types" class="rounded-sm text-[#c8202f] focus:ring-[#c8202f]">
                        {{ $t->label() }}
                    </label>
                @endforeach
            </div>
            @error('types') <flux:text class="text-rose-600">{{ $message }}</flux:text> @enderror
        </section>

        <section class="space-y-3">
            <flux:label>{{ __('Allowed restrictions') }}</flux:label>
            <div class="grid grid-cols-2 gap-2">
                @foreach (\App\Enums\FastingRestriction::cases() as $r)
                    <label class="flex items-center gap-2 rounded-md border border-zinc-200 p-2 text-sm dark:border-zinc-700">
                        <input type="checkbox" value="{{ $r->value }}" wire:model="restrictions" class="rounded-sm text-[#c8202f] focus:ring-[#c8202f]">
                        {{ $r->label() }}
                    </label>
                @endforeach
            </div>
        </section>

        <div class="flex justify-end gap-2">
            <flux:button :href="route('admin.fasting-campaigns.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
        </div>
    </form>
</div>