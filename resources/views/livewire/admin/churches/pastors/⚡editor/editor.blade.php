<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('admin.churches.index')" wire:navigate>{{ __('Churches') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.churches.edit', $church)" wire:navigate>{{ $church->name }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.churches.pastors.index', $church)" wire:navigate>{{ __('Pastors') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $form->assignment ? __('Edit assignment') : __('New assignment') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">
                {{ $form->assignment ? __('Edit assignment') : __('New assignment') }}
            </flux:heading>
            <flux:text class="mt-1">{{ $church->name }}</flux:text>
        </div>
        <flux:button :href="route('admin.churches.pastors.index', $church)" variant="ghost" wire:navigate>
            {{ __('Back to list') }}
        </flux:button>
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle" inline :heading="session('status')" />
    @endif

    <form wire:submit="save" class="space-y-5">
        <flux:radio.group wire:model.live="form.pastorMode" :label="__('Pastor')">
            <flux:radio value="existing" :label="__('Existing pastor')" />
            <flux:radio value="new" :label="__('Create a new pastor record')" />
        </flux:radio.group>

        @if ($form->pastorMode === 'existing')
            <flux:select wire:model="form.person_id" :label="__('Choose a pastor')" required>
                <option value="">{{ __('— Select —') }}</option>
                @foreach ($this->pastors as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </flux:select>
        @else
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="form.person_name" :label="__('Name')" required />
                <flux:input wire:model="form.person_email" :label="__('Email')" type="email" />
                <flux:input wire:model="form.person_phone" :label="__('Phone')" type="tel" />
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-3">
            <flux:select wire:model="form.function_id" :label="__('Function')" required>
                @foreach ($this->pastorFunctions as $fn)
                    <option value="{{ $fn->id }}">{{ $fn->name }}</option>
                @endforeach
            </flux:select>
            <flux:date-picker wire:model="form.start_date" :label="__('Start date')" />
            <flux:date-picker wire:model="form.end_date" :label="__('End date')" :placeholder="__('Active')" />
        </div>

        <div class="flex justify-end gap-2">
            <flux:button :href="route('admin.churches.pastors.index', $church)" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">{{ __('Save') }}</flux:button>
        </div>
    </form>
</div>