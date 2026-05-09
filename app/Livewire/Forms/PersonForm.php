<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\PersonNature;
use App\Enums\PersonType;
use App\Models\Person;
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

        return [
            'person_type' => ['required', 'in:'.implode(',', array_map(fn ($c) => $c->value, PersonType::cases()))],
            'name' => ['required', 'string', 'max:255'],
            'preferred_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => [
                'nullable', 'string', 'max:32',
                Rule::unique('persons', 'tax_id')->ignore($this->person?->id)->whereNotNull('tax_id'),
            ],
            'tax_id_type' => ['nullable', 'string', 'in:cpf,cnpj,passport,other'],
            'birthdate' => [$isIndividual ? 'nullable' : 'prohibited', 'date', 'before:today'],
            'gender' => [$isIndividual ? 'nullable' : 'prohibited', 'string', 'in:female,male,other'],
            'marital_status' => [$isIndividual ? 'nullable' : 'prohibited', 'string', 'max:32'],
            'natures' => ['array'],
            'natures.*' => ['string', 'in:'.implode(',', array_map(fn ($c) => $c->value, PersonNature::cases()))],
            'managing_church_id' => ['nullable', 'integer', 'exists:churches,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
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
        $this->gender = $person->gender ?? '';
        $this->marital_status = $person->marital_status ?? '';
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
