<?php

use App\Models\Person;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    /** Re-render on the global event so the banner reflects switches/clears. */
    #[On('acting-as-changed')]
    public function refresh(): void
    {
        unset($this->actingAsPerson);
    }

    #[Computed]
    public function actingAsPerson(): ?Person
    {
        return auth()->user()?->actingAsPerson();
    }

    public function stop(): void
    {
        session()->forget('acting_as_person_id');
        $this->dispatch('acting-as-changed');
    }
};
