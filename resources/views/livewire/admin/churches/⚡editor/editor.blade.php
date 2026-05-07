<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">
            {{ $church ? __('Edit church') : __('New church') }}
        </flux:heading>
        <flux:button :href="route('admin.churches.index')" variant="ghost" wire:navigate>{{ __('Back to list') }}</flux:button>
    </div>

    @if (session('status'))
        <div class="rounded-md bg-emerald-50 p-3 text-sm font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <section class="space-y-4">
            <flux:heading size="lg">{{ __('Church') }}</flux:heading>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="ecclesiastical_region_id" :label="__('Ecclesiastical region')" required>
                    <option value="">{{ __('— Select a region —') }}</option>
                    @foreach ($this->regions as $region)
                        <option value="{{ $region->id }}">{{ $region->code }} — {{ $region->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="type" :label="__('Type')" required>
                    @foreach (\App\Enums\ChurchType::cases() as $t)
                        <option value="{{ $t->value }}">{{ $t->label() }}</option>
                    @endforeach
                </flux:select>
            </div>

            <flux:input wire:model="name" :label="__('Name')" required />

            <flux:input wire:model="slug" :label="__('Slug')" :placeholder="__('Leave blank to auto-generate')" />

            <flux:input wire:model="address" :label="__('Address')" />

            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="city" :label="__('City')" />
                <flux:input wire:model="state" :label="__('State (UF)')" maxlength="2" />
                <flux:input wire:model="zip" :label="__('ZIP')" />
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="phone" :label="__('Phone')" type="tel" />
                <flux:input wire:model="email" :label="__('Email')" type="email" />
                <flux:input wire:model="timezone" :label="__('Timezone')" />
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="max_prayers_per_slot" :label="__('Max prayers per slot')" type="number" min="1" max="200" required />
                <flux:select wire:model="default_mode" :label="__('Default mode')">
                    @foreach (\App\Enums\LocationMode::cases() as $m)
                        <option value="{{ $m->value }}">{{ $m->label() }}</option>
                    @endforeach
                </flux:select>
                <flux:checkbox wire:model="is_active" :label="__('Active')" />
            </div>
        </section>

        @if (! $church)
            <section class="space-y-4 rounded-lg border border-amber-200 bg-amber-50/40 p-5 dark:border-amber-700 dark:bg-amber-900/20">
                <div>
                    <flux:heading size="lg">{{ __('Master user') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('A local manager account that will administer this church and create more admins for it.') }}</flux:text>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="master_name" :label="__('Name')" required />
                    <flux:input wire:model="master_email" :label="__('Email')" type="email" required />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="master_phone" :label="__('Phone')" type="tel" />
                    <flux:input wire:model="master_password" :label="__('Initial password')" type="password" required />
                </div>
            </section>
        @endif

        <div class="flex justify-end gap-2">
            <flux:button :href="route('admin.churches.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
        </div>
    </form>

    @if ($church)
        <div>
            <flux:heading size="lg">{{ __('Administrators of this church') }}</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500">
                {{ __('Use the Users page to manage church administrators.') }}
            </flux:text>
            <div class="mt-3">
                <flux:button :href="route('admin.users.index', ['church' => $church->id])" wire:navigate icon="users">
                    {{ __('Open users') }}
                </flux:button>
            </div>
        </div>
    @endif
</div>