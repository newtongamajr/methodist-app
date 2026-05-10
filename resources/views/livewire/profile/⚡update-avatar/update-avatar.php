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
        $path = $this->newAvatar->getRealPath();

        $user->clearMediaCollection('avatar');
        $user->addMedia($path)
            ->preservingOriginal()
            ->usingFileName('avatar.png')
            ->usingName('avatar')
            ->toMediaCollection('avatar');

        // Mirror the freshly-uploaded image to the linked Person ONLY if the
        // Person has no photo yet. Once the Person has any photo, future
        // avatar changes leave it alone — the inverse direction never applies.
        $person = $user->person;
        if ($person && $person->getFirstMedia('photo') === null) {
            $person->addMedia($path)
                ->preservingOriginal()
                ->usingFileName('photo.png')
                ->usingName('photo')
                ->toMediaCollection('photo');
        }

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
