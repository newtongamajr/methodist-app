<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public string $phone = '';

    public ?string $birthdate = null;

    public function mount(): void
    {
        $user = Auth::user();
        $this->phone = $user->phone ?? '';
        $this->birthdate = $user->birthdate?->format('Y-m-d');
    }

    public function updateContact(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'phone' => ['nullable', 'string', 'max:32'],
            'birthdate' => ['nullable', 'date', 'before:today'],
        ]);

        foreach (['phone', 'birthdate'] as $nullable) {
            if (($validated[$nullable] ?? null) === '') {
                $validated[$nullable] = null;
            }
        }

        $user->fill($validated);
        $user->save();

        $this->dispatch('profile-updated');
    }
};