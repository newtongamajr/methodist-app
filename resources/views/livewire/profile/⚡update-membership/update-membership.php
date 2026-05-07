<?php

use App\Enums\MemberType;
use App\Models\Church;
use App\Models\EcclesiasticalRegion;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public string $member_type = '';

    public ?int $region_id = null;

    public ?int $church_id = null;

    public function mount(): void
    {
        $user = Auth::user();
        $this->member_type = $user->member_type?->value ?? MemberType::Member->value;
        $this->church_id = $user->church_id;
        $this->region_id = $user->primaryChurch?->ecclesiastical_region_id;
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

    public function getRegionsProperty()
    {
        return EcclesiasticalRegion::query()->orderBy('display_order')->get(['id', 'code', 'name']);
    }

    public function getChurchesProperty()
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
            'member_type' => ['required', 'string', 'in:'.implode(',', array_map(fn (MemberType $c) => $c->value, MemberType::cases()))],
            'region_id' => ['nullable', 'integer', 'exists:ecclesiastical_regions,id'],
            'church_id' => ['nullable', 'integer', 'exists:churches,id'],
        ]);

        unset($validated['region_id']);

        if (($validated['church_id'] ?? null) === '') {
            $validated['church_id'] = null;
        }

        $user->fill($validated);
        $user->save();

        if ($validated['church_id'] ?? null) {
            $user->churches()->syncWithoutDetaching([
                $validated['church_id'] => ['is_primary' => true],
            ]);
        }

        $this->dispatch('profile-updated');
    }
};