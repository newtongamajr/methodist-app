<?php

use App\Enums\PersonType;
use App\Models\Person;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    #[Url(as: 'tab')]
    public string $tab = 'identity';

    /** Linked Person id, ensured-to-exist on first profile visit. */
    public ?int $personId = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        // Older accounts may predate the person link. The Profile tabs
        // delegate to the People components which are person-id-bound, so we
        // create a minimal Person on demand and attach it here. Future
        // visits skip this branch.
        if (! $user->person_id) {
            $person = Person::create([
                'person_type' => PersonType::Individual->value,
                'name' => $user->name,
            ]);
            $user->forceFill(['person_id' => $person->id])->save();
            $user->refresh();
        }

        $this->personId = $user->person_id;
    }
};