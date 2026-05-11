<?php

namespace App\Observers;

use App\Models\Group;
use Illuminate\Validation\ValidationException;

class GroupObserver
{
    public function saving(Group $group): void
    {
        $hasRegion = $group->ecclesiastical_region_id !== null;
        $hasDistrict = $group->district_id !== null;
        $hasChurch = $group->church_id !== null;

        if ($hasChurch && (! $hasDistrict || ! $hasRegion)) {
            throw ValidationException::withMessages([
                'church_id' => __('A church-level group must also reference its district and region.'),
            ]);
        }

        if ($hasDistrict && ! $hasRegion) {
            throw ValidationException::withMessages([
                'district_id' => __('A district-level group must also reference its region.'),
            ]);
        }
    }
}
