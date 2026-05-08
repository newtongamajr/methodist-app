<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('admin.fasting-campaigns.index')" wire:navigate>{{ __('Fasting campaigns') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $form->campaign ? __('Edit campaign') : __('New campaign') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">
            {{ $form->campaign ? __('Edit campaign') : __('New campaign') }}
        </flux:heading>
        <flux:button :href="route('admin.fasting-campaigns.index')" variant="ghost" wire:navigate>{{ __('Back to list') }}</flux:button>
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle" inline :heading="session('status')" />
    @endif

    <form wire:submit="save" class="space-y-5">
        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="form.name" :label="__('Name')" required />
            <flux:input wire:model="form.slug" :label="__('Slug')" :placeholder="__('Leave blank to auto-generate')" />
        </div>

        <flux:textarea wire:model="form.description" :label="__('Description')" rows="2" />

        <div class="grid gap-4 sm:grid-cols-3">
            <flux:date-picker wire:model="form.start_date" :label="__('Start date')" required />
            <flux:date-picker wire:model="form.end_date" :label="__('End date')" required />
            <flux:checkbox wire:model="form.is_active" :label="__('Active')" />
        </div>

        <section class="space-y-3">
            <flux:checkbox.group wire:model="form.types" :label="__('Allowed fasting types')" class="grid grid-cols-2 gap-2">
                @foreach (\App\Enums\FastingType::cases() as $t)
                    <flux:checkbox wire:key="fasting-type-{{ $t->value }}" value="{{ $t->value }}" :label="$t->label()" />
                @endforeach
            </flux:checkbox.group>
            @error('form.types') <flux:text class="text-rose-600">{{ $message }}</flux:text> @enderror
        </section>

        <section class="space-y-3">
            <flux:checkbox.group wire:model="form.restrictions" :label="__('Allowed restrictions')" class="grid grid-cols-2 gap-2">
                @foreach (\App\Enums\FastingRestriction::cases() as $r)
                    <flux:checkbox wire:key="fasting-restriction-{{ $r->value }}" value="{{ $r->value }}" :label="$r->label()" />
                @endforeach
            </flux:checkbox.group>
        </section>

        <div class="flex justify-end gap-2">
            <flux:button :href="route('admin.fasting-campaigns.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">{{ __('Save') }}</flux:button>
        </div>
    </form>
</div>