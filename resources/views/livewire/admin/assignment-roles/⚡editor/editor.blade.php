<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('admin.assignment-roles.index')" wire:navigate>{{ __('Assignment roles') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $form->assignmentRole ? __('Edit assignment role') : __('New assignment role') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">
            {{ $form->assignmentRole ? $form->assignmentRole->name : __('New assignment role') }}
        </flux:heading>
        <div class="flex gap-2">
            @if ($form->assignmentRole)
                <flux:button :href="route('admin.assignment-roles.people', $form->assignmentRole)" wire:navigate icon="user-group" variant="ghost">
                    {{ __('People with this role') }}
                </flux:button>
            @endif
            <flux:button :href="route('admin.assignment-roles.index')" variant="ghost" wire:navigate>{{ __('Back to list') }}</flux:button>
        </div>
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle" inline :heading="session('status')" />
    @endif

    <form wire:submit="save" class="space-y-5">
        <flux:input wire:model="form.name" :label="__('Name')" required />

        <flux:input wire:model="form.slug" :label="__('Slug')" :placeholder="__('Leave blank to auto-generate')" />

        <flux:textarea wire:model="form.description" :label="__('Description')" rows="4" />

        <flux:checkbox wire:model="form.is_active" :label="__('Active')" />

        <div class="flex justify-end gap-2">
            <flux:button :href="route('admin.assignment-roles.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">{{ __('Save') }}</flux:button>
        </div>
    </form>
</div>