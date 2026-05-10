<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\PersonNature;
use App\Enums\PersonType;
use App\Models\Person;
use App\Support\TaxIdValidator;
use Illuminate\Validation\Rule;
use Livewire\Form;

class PersonForm extends Form
{
    public ?Person $person = null;

    public string $person_type = 'individual';

    public string $name = '';

    public string $preferred_name = '';

    public string $tax_id = '';

    public string $tax_id_type = '';

    public string $birthdate = '';

    public string $gender = '';

    public string $marital_status = '';

    /** @var array<int, string> */
    public array $natures = [];

    public ?int $managing_church_id = null;

    public string $notes = '';

    public function rules(): array
    {
        $isIndividual = $this->person_type === PersonType::Individual->value;

        // Nature whitelist depends on person_type — individuals can't carry an
        // org nature and vice-versa.
        $allowedNatures = array_keys(PersonNature::optionsForPersonType($this->person_type));

        return [
            'person_type' => ['required', 'in:'.implode(',', array_map(fn ($c) => $c->value, PersonType::cases()))],
            'name' => ['required', 'string', 'max:255'],
            'preferred_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => [
                'nullable', 'string', 'max:32',
                Rule::unique('persons', 'tax_id')->ignore($this->person?->id)->whereNotNull('tax_id'),
                // Run the CPF/CNPJ checksum here (instead of PersonObserver) so the
                // resulting error key is `form.tax_id`, which the input under
                // wire:model="form.tax_id" actually listens for. The previous
                // observer-based check threw with key `tax_id` and the message
                // never reached the input.
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value) {
                        return;
                    }
                    $type = strtolower((string) $this->tax_id_type);
                    $digits = TaxIdValidator::normalize((string) $value);

                    $valid = match ($type) {
                        'cpf' => TaxIdValidator::validateCpf($digits),
                        'cnpj' => TaxIdValidator::validateCnpj($digits),
                        default => true,
                    };

                    if (! $valid) {
                        $fail(__('The :type number is invalid.', ['type' => strtoupper($type)]));
                    }
                },
            ],
            'tax_id_type' => ['nullable', 'string', 'in:cpf,cnpj,passport,other'],
            // birthdate column doubles as the foundation_date for organizations
            // — a single column, the UI label flips per type. before_or_equal
            // accepts today (a Person record for a baby born today is valid;
            // an organization founded today is also valid).
            'birthdate' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => [
                $isIndividual ? 'nullable' : 'prohibited',
                'string',
                'in:'.implode(',', array_map(fn ($c) => $c->value, Gender::cases())),
            ],
            'marital_status' => [
                $isIndividual ? 'nullable' : 'prohibited',
                'string',
                'in:'.implode(',', array_map(fn ($c) => $c->value, MaritalStatus::cases())),
            ],
            'natures' => ['array'],
            'natures.*' => ['string', 'in:'.implode(',', $allowedNatures)],
            'managing_church_id' => ['nullable', 'integer', 'exists:churches,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * Custom messages for rules whose default text would leak literal English
     * placeholders into pt_BR (`:date` rendered as the keyword "today").
     */
    public function messages(): array
    {
        return [
            'birthdate.before_or_equal' => __(':attribute cannot be in the future.'),
            'birthdate.date' => __(':attribute is not a valid date.'),
        ];
    }

    /**
     * Localized labels used by the validator when interpolating `:attribute`.
     * Without this every Form-Object error reads "O campo birthdate ..." with
     * the literal English property name — which is what the UI looked like
     * before. The label flips between Birthdate and Foundation date depending
     * on the person_type so the message matches the field's visible label.
     */
    public function validationAttributes(): array
    {
        $birthdateLabel = $this->person_type === PersonType::Organization->value
            ? __('Foundation date')
            : __('Birthdate');

        return [
            'name' => __('Name'),
            'preferred_name' => __('Preferred name'),
            'tax_id' => __('Tax ID'),
            'tax_id_type' => __('Tax ID type'),
            'birthdate' => $birthdateLabel,
            'gender' => __('Gender'),
            'marital_status' => __('Marital status'),
            'natures' => __('Natures'),
            'managing_church_id' => __('Managing church'),
            'notes' => __('Notes'),
        ];
    }

    public function setPerson(Person $person): void
    {
        $this->person = $person;
        $this->person_type = $person->person_type?->value ?? PersonType::Individual->value;
        $this->name = $person->name;
        $this->preferred_name = $person->preferred_name ?? '';
        $this->tax_id = $person->tax_id ?? '';
        $this->tax_id_type = $person->tax_id_type ?? '';
        $this->birthdate = $person->birthdate?->format('Y-m-d') ?? '';
        $this->gender = $person->gender?->value ?? '';
        $this->marital_status = $person->marital_status?->value ?? '';
        $this->natures = $person->natures ?? [];
        $this->managing_church_id = $person->managing_church_id;
        $this->notes = $person->notes ?? '';
    }

    public function save(): Person
    {
        $data = $this->validate();

        // Empty strings → null for nullable fields.
        foreach (['preferred_name', 'tax_id', 'tax_id_type', 'birthdate', 'gender', 'marital_status', 'notes'] as $k) {
            if (($data[$k] ?? null) === '') {
                $data[$k] = null;
            }
        }

        if ($this->person) {
            $this->person->update($data);
        } else {
            $this->person = Person::create($data);
        }

        return $this->person;
    }
}
