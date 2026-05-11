<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('admin.users.index')" wire:navigate>{{ __('Administrators') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $form->user ? __('Edit administrator') : __('New administrator') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">
            {{ $form->user ? __('Edit administrator') : __('New administrator') }}
        </flux:heading>
        <flux:button :href="route('admin.users.index')" variant="ghost" wire:navigate>{{ __('Back to list') }}</flux:button>
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle" inline :heading="session('status')" />
    @endif

    <form wire:submit="save" class="space-y-5">
        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="form.name" :label="__('Name')" required />
            <flux:input wire:model="form.email" :label="__('Email')" type="email" required />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="form.phone" :label="__('Phone')" type="tel" />
            <flux:input
                wire:model="form.password"
                :label="$form->user ? __('New password (leave blank to keep current)') : __('Initial password')"
                type="password"
                :required="! $form->user"
            />
        </div>

        <flux:field>
            <flux:label>{{ __('Churches this user can administer') }}</flux:label>
            <flux:description>
                {{ __('Tick every church the user should manage. The primary one becomes their default context after sign-in.') }}
            </flux:description>
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($this->selectableChurches as $church)
                    <label wire:key="user-edit-church-{{ $church['id'] }}" class="flex items-center gap-2 rounded-md border border-zinc-200 p-2 text-sm dark:border-zinc-700">
                        <input
                            type="checkbox"
                            value="{{ $church['id'] }}"
                            wire:model.live="form.church_ids"
                            class="rounded-sm text-accent focus:ring-accent"
                        >
                        <span class="flex-1">{{ $church['name'] }}</span>
                        <input
                            type="radio"
                            name="primary_church_id"
                            value="{{ $church['id'] }}"
                            wire:model="form.primary_church_id"
                            @disabled(! in_array($church['id'], $form->church_ids, true))
                            class="text-accent"
                            title="{{ __('Mark as primary') }}"
                        >
                    </label>
                @endforeach
            </div>
            <flux:error name="form.church_ids" />
        </flux:field>

        @if ($this->isSuper)
            <flux:select wire:model="form.role" :label="__('Role')" required>
                @foreach ($this->availableRoles as $r)
                    <option value="{{ $r }}">{{ $r }}</option>
                @endforeach
            </flux:select>
        @else
            <flux:input value="local_admin" :label="__('Role')" disabled />
        @endif

        <flux:select wire:model="form.locale" :label="__('Language')">
            @foreach (\App\Enums\AppLocale::cases() as $loc)
                <option value="{{ $loc->value }}">{{ $loc->label() }}</option>
            @endforeach
        </flux:select>

        <div class="flex justify-end gap-2">
            <flux:button :href="route('admin.users.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">{{ __('Save') }}</flux:button>
        </div>
    </form>
</div>