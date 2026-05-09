<?php

namespace App\Observers;

use App\Enums\PersonNature;
use App\Models\Church;
use App\Models\District;
use App\Models\EcclesiasticalRegion;
use App\Models\Person;
use App\Support\TaxIdValidator;
use Illuminate\Validation\ValidationException;

class PersonObserver
{
    public function saving(Person $person): void
    {
        if (! $person->tax_id) {
            return;
        }

        $person->tax_id = TaxIdValidator::normalize($person->tax_id);
        $type = strtolower((string) $person->tax_id_type);

        $valid = match ($type) {
            'cpf' => TaxIdValidator::validateCpf($person->tax_id),
            'cnpj' => TaxIdValidator::validateCnpj($person->tax_id),
            default => true,
        };

        if (! $valid) {
            throw ValidationException::withMessages([
                'tax_id' => __('The :type number is invalid.', ['type' => strtoupper($type)]),
            ]);
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
