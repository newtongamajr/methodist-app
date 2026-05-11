<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Models\PersonAddress;
use Livewire\Form;

class PersonAddressForm extends Form
{
    public ?PersonAddress $address = null;

    public ?int $person_id = null;

    public string $label = '';

    public string $street = '';

    public string $number = '';

    public string $complement = '';

    public string $neighborhood = '';

    public string $city = '';

    public string $state = '';

    public string $zip = '';

    public string $country = 'BR';

    public bool $is_primary = false;

    public function rules(): array
    {
        return [
            'person_id' => ['required', 'integer', 'exists:persons,id'],
            'label' => ['nullable', 'string', 'max:64'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:32'],
            'complement' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:2'],
            'zip' => ['nullable', 'string', 'max:16'],
            'country' => ['required', 'string', 'size:2'],
            'is_primary' => ['boolean'],
        ];
    }

    public function setAddress(PersonAddress $address): void
    {
        $this->address = $address;
        $this->person_id = $address->person_id;
        $this->label = $address->label ?? '';
        $this->street = $address->street ?? '';
        $this->number = $address->number ?? '';
        $this->complement = $address->complement ?? '';
        $this->neighborhood = $address->neighborhood ?? '';
        $this->city = $address->city ?? '';
        $this->state = $address->state ?? '';
        $this->zip = $address->zip ?? '';
        $this->country = $address->country ?? 'BR';
        $this->is_primary = $address->is_primary;
    }

    public function save(): PersonAddress
    {
        $data = $this->validate();
        foreach (['label', 'street', 'number', 'complement', 'neighborhood', 'city', 'state', 'zip'] as $k) {
            if (($data[$k] ?? null) === '') {
                $data[$k] = null;
            }
        }

        if ($this->address) {
            $this->address->update($data);
        } else {
            $this->address = PersonAddress::create($data);
        }

        if ($this->address->is_primary) {
            PersonAddress::query()
                ->where('person_id', $this->address->person_id)
                ->whereKeyNot($this->address->id)
                ->update(['is_primary' => false]);
        }

        return $this->address;
    }
}
