<?php

namespace App\Observers;

use App\Enums\PersonNature;
use App\Models\Church;
use App\Models\District;
use App\Models\EcclesiasticalRegion;
use App\Models\Person;
use App\Support\TaxIdValidator;

class PersonObserver
{
    public function saving(Person $person): void
    {
        // Normalize the tax ID to digits-only before persistence. The
        // checksum check itself moved to PersonForm::rules() so the error
        // lands at `form.tax_id` (the key the UI actually listens for) —
        // throwing from here would key it as plain `tax_id` and the message
        // would never display under the input.
        if ($person->tax_id) {
            $person->tax_id = TaxIdValidator::normalize($person->tax_id);
        }
    }

    /**
     * When an org-Person's name changes, mirror it back to the linked org
     * row. The reverse direction (org → person) is already handled by the
     * org editors. This closes the drift gap when admins edit the Person
     * directly via /admin/people.
     */
    public function updated(Person $person): void
    {
        if (! $person->wasChanged('name')) {
            return;
        }

        if ($person->hasNature(PersonNature::EcclesiasticalRegion)
            || $person->hasNature(PersonNature::NationalHeadquarters)) {
            EcclesiasticalRegion::query()
                ->where('person_id', $person->id)
                ->update(['name' => $person->name]);
        }

        if ($person->hasNature(PersonNature::District)) {
            District::query()
                ->where('person_id', $person->id)
                ->update(['name' => $person->name]);
        }

        if ($person->hasNature(PersonNature::Church)) {
            Church::query()
                ->where('person_id', $person->id)
                ->update(['name' => $person->name]);
        }
    }
}
