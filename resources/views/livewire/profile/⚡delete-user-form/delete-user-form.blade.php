<section class="space-y-6">
    <header>
        <flux:heading size="lg">{{ __('Delete Account') }}</flux:heading>
        <flux:text class="mt-1">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </flux:text>
    </header>

    <flux:modal.trigger name="confirm-user-deletion">
        <flux:button variant="danger">{{ __('Delete Account') }}</flux:button>
    </flux:modal.trigger>

    <flux:modal name="confirm-user-deletion" class="md:w-md">
        <form wire:submit="deleteUser" class="space-y-5">
            <flux:heading size="lg">{{ __('Are you sure you want to delete your account?') }}</flux:heading>
            <flux:text>{{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}</flux:text>

            <flux:input wire:model="password" :label="__('Password')" type="password" required />

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="danger">{{ __('Delete Account') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>