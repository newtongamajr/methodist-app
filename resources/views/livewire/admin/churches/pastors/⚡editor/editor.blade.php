<div class="space-y-6">
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
        <div class="rounded-md bg-emerald-50 p-3 text-sm font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-5">
        <flux:radio.group wire:model.live="form.pastorMode" :label="__('Pastor')">
            <flux:radio value="existing" :label="__('Existing pastor')" />
            <flux:radio value="new" :label="__('Create a new pastor record')" />
        </flux:radio.group>

        @if ($form->pastorMode === 'existing')
            <flux:select wire:model="form.pastor_id" :label="__('Choose a pastor')" required>
                <option value="">{{ __('— Select —') }}</option>
                @foreach ($this->pastors as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}@if ($p->email) — {{ $p->email }}@endif</option>
                @endforeach
            </flux:select>
        @else
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="form.pastor_name" :label="__('Name')" required />
                <flux:input wire:model="form.pastor_email" :label="__('Email')" type="email" />
                <flux:input wire:model="form.pastor_phone" :label="__('Phone')" type="tel" />
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-3">
            <flux:select wire:model="form.role" :label="__('Role')" required>
                @foreach (\App\Enums\PastorRole::cases() as $r)
                    <option value="{{ $r->value }}">{{ $r->label() }}</option>
                @endforeach
            </flux:select>
            <flux:input wire:model="form.start_date" :label="__('Start date')" type="date" />
            <flux:input wire:model="form.end_date" :label="__('End date')" type="date" :placeholder="__('Active')" />
        </div>

        <flux:input wire:model="form.display_order" :label="__('Display order')" type="number" min="0" max="99" />

        <div class="flex justify-end gap-2">
            <flux:button :href="route('admin.churches.pastors.index', $church)" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">{{ __('Save') }}</flux:button>
        </div>
    </form>
</div>