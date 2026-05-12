<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('Assignment roles') }}</flux:heading>
        <flux:button :href="route('admin.assignment-roles.create')" variant="primary" icon="plus" wire:navigate>
            {{ __('New assignment role') }}
        </flux:button>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search by name or slug…')" icon="magnifying-glass" class="lg:col-span-2" />

        <flux:select wire:model.live="statusFilter" variant="listbox" clearable :placeholder="__('All statuses')">
            <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
            <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
        </flux:select>
    </div>

    @if ($this->assignmentRoles->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
            {{ __('No assignment roles yet.') }}
        </div>
    @else
        <flux:table :paginate="$this->assignmentRoles">
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDir" wire:click="sort('name')">{{ __('Name') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'slug'" :direction="$sortDir" wire:click="sort('slug')">{{ __('Slug') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'assignments_count'" :direction="$sortDir" wire:click="sort('assignments_count')">{{ __('People') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'is_active'" :direction="$sortDir" wire:click="sort('is_active')">{{ __('Active') }}</flux:table.column>
                <flux:table.column align="end">&nbsp;</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->assignmentRoles as $role)
                    <flux:table.row :key="'arole-'.$role->id">
                        <flux:table.cell variant="strong">{{ $role->name }}</flux:table.cell>
                        <flux:table.cell><flux:badge color="zinc">{{ $role->slug }}</flux:badge></flux:table.cell>
                        <flux:table.cell>{{ $role->assignments_count }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$role->is_active ? 'emerald' : 'zinc'">
                                {{ $role->is_active ? __('Active') : __('Inactive') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="inline-flex items-center gap-1">
                                <flux:tooltip :content="__('View')">
                                    <flux:button wire:click="openView({{ $role->id }})" size="sm" variant="ghost" icon="eye" />
                                </flux:tooltip>
                                <flux:tooltip :content="__('Edit')">
                                    <flux:button :href="route('admin.assignment-roles.edit', $role)" wire:navigate size="sm" variant="ghost" icon="pencil-square" />
                                </flux:tooltip>
                                <flux:tooltip :content="__('People with this role and which group')">
                                    <flux:button :href="route('admin.assignment-roles.people', $role)" wire:navigate size="sm" variant="ghost" icon="user-group" />
                                </flux:tooltip>
                                <flux:tooltip :content="__('Delete')">
                                    <flux:modal.trigger :name="'delete-assignment-role-'.$role->id">
                                        <flux:button size="sm" variant="ghost" icon="trash" />
                                    </flux:modal.trigger>
                                </flux:tooltip>
                                <x-confirm-delete
                                    :name="'delete-assignment-role-'.$role->id"
                                    :heading="__('Delete this assignment role?')"
                                    action="delete({{ $role->id }})"
                                />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif

    <flux:modal name="view-assignment-role" class="md:max-w-lg">
        @if ($this->viewingRole)
            <div class="space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <flux:heading size="lg">{{ $this->viewingRole->name }}</flux:heading>
                    <flux:badge :color="$this->viewingRole->is_active ? 'emerald' : 'zinc'">
                        {{ $this->viewingRole->is_active ? __('Active') : __('Inactive') }}
                    </flux:badge>
                </div>

                <dl class="grid gap-3 sm:grid-cols-2 text-sm">
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Slug') }}</dt>
                        <dd><flux:badge color="zinc">{{ $this->viewingRole->slug }}</flux:badge></dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('People') }}</dt>
                        <dd>{{ $this->viewingRole->assignments_count }}</dd>
                    </div>
                </dl>

                @if ($this->viewingRole->description)
                    <div class="text-sm">
                        <dt class="text-zinc-500 dark:text-zinc-400 mb-1">{{ __('Description') }}</dt>
                        <dd class="whitespace-pre-line">{{ $this->viewingRole->description }}</dd>
                    </div>
                @endif

                <div class="flex justify-end gap-2 pt-2">
                    <flux:button :href="route('admin.assignment-roles.people', $this->viewingRole)" wire:navigate variant="ghost" icon="user-group">{{ __('People with this role') }}</flux:button>
                    <flux:button :href="route('admin.assignment-roles.edit', $this->viewingRole)" wire:navigate variant="primary" icon="pencil-square">{{ __('Edit') }}</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>