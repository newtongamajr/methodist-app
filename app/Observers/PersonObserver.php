<?php

namespace App\Observers;

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
}
