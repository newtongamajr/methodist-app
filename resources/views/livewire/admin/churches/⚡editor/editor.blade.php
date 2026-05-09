<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('admin.churches.index')" wire:navigate>{{ __('Churches') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $form->church ? __('Edit church') : __('New church') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">
            {{ $form->church ? __('Edit church') : __('New church') }}
        </flux:heading>
        <flux:button :href="route('admin.churches.index')" variant="ghost" wire:navigate>{{ __('Back to list') }}</flux:button>
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle" inline :heading="session('status')" />
    @endif

    <form wire:submit="save" class="space-y-6">
        <section class="space-y-4">
            <flux:heading size="lg">{{ __('Church') }}</flux:heading>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select
                    wire:model.live="form.ecclesiastical_region_id"
                    variant="listbox"
                    searchable
                    clearable
                    :label="__('Ecclesiastical region')"
                    :placeholder="__('Pick a region…')"
                    required
                >
                    @foreach ($this->regions as $region)
                        <flux:select.option :value="$region->id">{{ $region->code }} — {{ $region->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="form.type" :label="__('Type')" required>
                    @foreach (\App\Enums\ChurchType::cases() as $t)
                        <option value="{{ $t->value }}">{{ $t->label() }}</option>
                    @endforeach
                </flux:select>
            </div>

            @if ($form->ecclesiastical_region_id)
                <flux:select
                    wire:model="form.district_id"
                    variant="listbox"
                    searchable
                    clearable
                    :label="__('District')"
                    :placeholder="$this->districts->isEmpty() ? __('No districts in this region yet.') : __('Pick a district…')"
                    :disabled="$this->districts->isEmpty()"
                    :required="$form->districtIsRequired()"
                >
                    @foreach ($this->districts as $district)
                        <flux:select.option :value="$district->id">{{ $district->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            @endif

            <flux:input wire:model="form.name" :label="__('Name')" required />

            <flux:input wire:model="form.slug" :label="__('Slug')" :placeholder="__('Leave blank to auto-generate')" />

            <flux:input wire:model="form.address" :label="__('Address')" />

            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="form.city" :label="__('City')" />
                <flux:input wire:model="form.state" :label="__('State (UF)')" maxlength="2" />
                <flux:input wire:model="form.zip" :label="__('ZIP')" />
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="form.phone" :label="__('Phone')" type="tel" />
                <flux:input wire:model="form.email" :label="__('Email')" type="email" />
                <flux:input wire:model="form.timezone" :label="__('Timezone')" />
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="form.max_prayers_per_slot" :label="__('Max prayers per slot')" type="number" min="1" max="200" required />
                <flux:select wire:model="form.default_mode" :label="__('Default mode')">
                    @foreach (\App\Enums\LocationMode::cases() as $m)
                        <option value="{{ $m->value }}">{{ $m->label() }}</option>
                    @endforeach
                </flux:select>
                <flux:checkbox wire:model="form.is_active" :label="__('Active')" />
            </div>
        </section>

        @if (! $form->church)
            <section class="space-y-4 rounded-lg border border-amber-200 bg-amber-50/40 p-5 dark:border-amber-700 dark:bg-amber-900/20">
                <div>
                    <flux:heading size="lg">{{ __('Master user') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('A local manager account that will administer this church and create more admins for it.') }}</flux:text>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="form.master_name" :label="__('Name')" required />
                    <flux:input wire:model="form.master_email" :label="__('Email')" type="email" required />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="form.master_phone" :label="__('Phone')" type="tel" />
                    <flux:input wire:model="form.master_password" :label="__('Initial password')" type="password" required />
                </div>
            </section>
        @endif

        <div class="flex justify-end gap-2">
            <flux:button :href="route('admin.churches.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">{{ __('Save') }}</flux:button>
        </div>
    </form>

    @if ($form->church)
        <div>
            <flux:heading size="lg">{{ __('Administrators of this church') }}</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500">
                {{ __('Use the Users page to manage church administrators.') }}
            </flux:text>
            <div class="mt-3">
                <flux:button :href="route('admin.users.index', ['church' => $form->church->id])" wire:navigate icon="users">
                    {{ __('Open users') }}
                </flux:button>
            </div>
        </div>
    @endif
</div>