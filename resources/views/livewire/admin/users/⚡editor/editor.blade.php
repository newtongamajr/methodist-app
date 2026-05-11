<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('admin.users.index')" wire:navigate>{{ __('Administrators') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $form->user ? __('Edit administrator') : __('New administrator') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">
            {{ $form->user ? __('Edit administrator') : __('New administrator') }}
        </flux:heading>
        <div class="flex gap-2">
            @if ($form->user)
                <flux:tooltip :content="__('Manage which churches this user administers')">
                    <flux:button :href="route('admin.users.churches', $form->user)" wire:navigate icon="building-library" variant="ghost">
                        {{ __('Manage churches') }}
                    </flux:button>
                </flux:tooltip>
            @endif
            <flux:button :href="route('admin.users.index')" variant="ghost" wire:navigate>{{ __('Back to list') }}</flux:button>
        </div>
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
            <flux:input
                wire:model="form.password"
                :label="$form->user ? __('New password (leave blank to keep current)') : __('Initial password')"
                type="password"
                viewable
                :required="! $form->user"
            />
            <flux:input
                wire:model="form.password_confirmation"
                :label="__('Confirm password')"
                type="password"
                viewable
                :required="! $form->user"
            />
        </div>

        @if ($this->isSuper)
            <flux:select wire:model="form.role" :label="__('Role')" required>
                @foreach (\App\Models\Role::query()->whereIn('name', $this->availableRoles)->orderBy('name')->get() as $r)
                    <option value="{{ $r->name }}">{{ $r->description ?: $r->name }}</option>
                @endforeach
            </flux:select>
        @else
            <flux:input value="local_admin" :label="__('Role')" disabled />
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:select wire:model="form.locale" :label="__('Language')" required>
                @foreach (\App\Enums\AppLocale::cases() as $loc)
                    <option value="{{ $loc->value }}">{{ $loc->label() }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model="form.appearance" :label="__('Appearance')" required>
                @foreach (\App\Enums\AppAppearance::cases() as $a)
                    <option value="{{ $a->value }}">{{ $a->label() }}</option>
                @endforeach
            </flux:select>
        </div>

        <div class="flex justify-end gap-2">
            <flux:button :href="route('admin.users.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">{{ __('Save') }}</flux:button>
        </div>
    </form>
</div>
