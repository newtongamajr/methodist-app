<?php

use App\Enums\PersonNature;
use App\Enums\PersonType;
use App\Models\Church;
use App\Models\EcclesiasticalRegion;
use App\Models\Person;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $nature = '';

    public ?int $region_id = null;

    public ?int $church_id = null;

    public function mount(): void
    {
        $user = Auth::user();
        $person = $user->person;
        $this->nature = $person?->natures[0] ?? PersonNature::Member->value;
        $this->church_id = $person?->managing_church_id;
        $this->region_id = $person?->managingChurch?->ecclesiastical_region_id;
    }

    public function updatedRegionId(): void
    {
        if ($this->church_id) {
            $church = Church::find($this->church_id);
            if (! $church || $church->ecclesiastical_region_id !== $this->region_id) {
                $this->church_id = null;
            }
        }
    }

    #[Computed]
    public function regions(): Collection
    {
        return EcclesiasticalRegion::query()->orderBy('display_order')->get(['id', 'code', 'name']);
    }

    #[Computed]
    public function churches(): Collection
    {
        if (! $this->region_id) {
            return collect();
        }

        return Church::query()
            ->where('ecclesiastical_region_id', $this->region_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'state']);
    }

    public function updateMembership(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'nature' => ['required', 'string', 'in:'.implode(',', array_map(fn (PersonNature $c) => $c->value, PersonNature::cases()))],
            'region_id' => ['nullable', 'integer', 'exists:ecclesiastical_regions,id'],
            'church_id' => ['nullable', 'integer', 'exists:churches,id'],
        ]);

        if (($validated['church_id'] ?? null) === '') {
            $validated['church_id'] = null;
        }

        DB::transaction(function () use ($user, $validated) {
            $person = $user->person ?? Person::create([
                'person_type' => PersonType::Individual->value,
                'name' => $user->name,
            ]);
            if (! $user->person) {
                $user->person_id = $person->id;
                $user->save();
            }

            $person->natures = [$validated['nature']];
            $person->managing_church_id = $validated['church_id'] ?? null;
            $person->save();

            if (! empty($validated['church_id'])) {
                $user->churches()->syncWithoutDetaching([
                    $validated['church_id'] => ['is_primary' => true],
                ]);
            }
        });

        $this->dispatch('profile-updated');
    }
};
