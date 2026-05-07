<?php

use App\Enums\AppAppearance;
use App\Enums\AppLocale;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public string $locale = '';

    public string $appearance = '';

    public function mount(): void
    {
        $user = Auth::user();
        $this->locale = $user->locale ?? AppLocale::PtBR->value;
        $this->appearance = $user->appearance ?? AppAppearance::System->value;
    }

    public function updatePreferences(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'locale' => ['required', 'string', 'in:'.implode(',', AppLocale::values())],
            'appearance' => ['required', 'string', 'in:'.implode(',', AppAppearance::values())],
        ]);

        $user->fill($validated);
        $user->save();

        session(['locale' => $user->locale, 'appearance' => $user->appearance]);

        $this->dispatch('profile-updated');
    }
};