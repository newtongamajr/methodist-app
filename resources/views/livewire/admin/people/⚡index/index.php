<?php

use App\Livewire\Concerns\ManagesPersons;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    use ManagesPersons;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('users.manage') || auth()->user()?->can('users.manage.local'), 403);
    }

    protected function getPersonNature(): ?string
    {
        return null; // all-persons listing
    }
};
