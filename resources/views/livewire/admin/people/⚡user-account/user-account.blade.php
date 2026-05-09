<div class="space-y-6">
    @if (session('user-status'))
        <flux:callout variant="success" icon="check-circle" inline :heading="session('user-status')" />
    @endif

    @error('user')
        <div class="rounded-md bg-rose-50 p-3 text-sm font-medium text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">{{ $message }}</div>
    @enderror

    @if ($person->person_type?->value === 'organization')
        <flux:callout variant="info" icon="information-circle" inline :heading="__('Only individual people can have a user account. This record is an organization.')" />
    @elseif ($this->user)
        <flux:heading size="lg">{{ __('Linked user account') }}</flux:heading>
        <div class="rounded-lg border border-zinc-200 p-5 dark:border-zinc-700">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-zinc-500">{{ __('Email') }}</dt>
                    <dd class="mt-1 font-medium">{{ $this->user->email }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-zinc-500">{{ __('Roles') }}</dt>
                    <dd class="mt-1 flex flex-wrap gap-1">
                        @forelse ($this->user->roles as $role)
                            <flux:badge wire:key="user-role-{{ $role->id }}" color="zinc">{{ $role->name }}</flux:badge>
                        @empty
                            <flux:text class="text-sm text-zinc-500">—</flux:text>
                        @endforelse
                    </dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-zinc-500">{{ __('Last sign-in') }}</dt>
                    <dd class="mt-1 text-sm">{{ $this->user->updated_at?->isoFormat('LL') }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-zinc-500">{{ __('Locale') }}</dt>
                    <dd class="mt-1 text-sm">{{ $this->user->locale }}</dd>
                </div>
            </dl>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button :href="route('admin.users.edit', $this->user)" wire:navigate variant="primary" icon="pencil-square">
                {{ __('Edit user') }}
            </flux:button>
            @can('users.manage')
                <flux:modal.trigger name="disconnect-user-account">
                    <flux:button variant="ghost" icon="x-mark">{{ __('Disconnect user') }}</flux:button>
                </flux:modal.trigger>
                <x-confirm-delete
                    name="disconnect-user-account"
                    :heading="__('Disconnect this user account?')"
                    :message="__('The Person record will stay; the User row (login + roles) will be deleted.')"
                    :confirmLabel="__('Disconnect')"
                    action="disconnect"
                />
            @endcan
        </div>
    @else
        <flux:heading size="lg">{{ __('No user account linked yet') }}</flux:heading>
        <flux:text>{{ __('Create a login for this person so they can sign in. The Person record stays as the source of truth for identity and contacts; the User row only carries credentials and roles.') }}</flux:text>

        <form wire:submit="createUser" class="space-y-4">
            <flux:input wire:model="email" :label="__('Email')" type="email" required />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="password" :label="__('Initial password')" type="password" required />
                <flux:select wire:model="locale" :label="__('Language')" required>
                    @foreach (\App\Enums\AppLocale::cases() as $loc)
                        <option value="{{ $loc->value }}">{{ $loc->label() }}</option>
                    @endforeach
                </flux:select>
            </div>
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="createUser">
                    {{ __('Create user account') }}
                </flux:button>
            </div>
        </form>
    @endif
</div>
