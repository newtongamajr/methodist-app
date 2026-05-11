<?php

use App\Enums\PersonNature;
use App\Models\Person;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public ?Person $person = null;

    #[Url(as: 'tab')]
    public string $tab = 'identity';

    /** Pre-seed nature for new-person flow (e.g. visitor quick-add via ?nature=visitor). */
    public ?string $natureSeed = null;

    public function mount(?int $personId = null, ?string $nature = null): void
    {
        abort_unless(auth()->user()?->can('users.manage') || auth()->user()?->can('users.manage.local'), 403);

        if ($personId) {
            $this->person = Person::findOrFail($personId);
        } elseif ($nature && PersonNature::tryFrom($nature)) {
            $this->natureSeed = $nature;
        }
    }
};
