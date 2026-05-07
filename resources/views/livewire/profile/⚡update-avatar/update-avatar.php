<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    #[Validate(['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'])]
    public $newAvatar = null;

    public function saveAvatar(): void
    {
        $this->validate();

        if (! $this->newAvatar) {
            return;
        }

        $user = Auth::user();
        $user->clearMediaCollection('avatar');
        $user->addMedia($this->newAvatar->getRealPath())
            ->usingFileName('avatar.png')
            ->usingName('avatar')
            ->toMediaCollection('avatar');

        $this->newAvatar = null;
        $this->dispatch('avatar-updated');
    }

    public function removeAvatar(): void
    {
        Auth::user()->clearMediaCollection('avatar');
        $this->dispatch('avatar-updated');
    }

    public function with(): array
    {
        return [
            'avatarUrl' => Auth::user()->avatarUrl('md'),
            'thumbUrl' => Auth::user()->avatarUrl('thumb'),
        ];
    }
};
