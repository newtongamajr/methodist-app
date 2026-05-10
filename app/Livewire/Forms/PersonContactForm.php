<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\Country;
use App\Enums\PersonContactType;
use App\Models\PersonContact;
use Livewire\Form;

class PersonContactForm extends Form
{
    public ?PersonContact $contact = null;

    public ?int $person_id = null;

    public string $type = 'phone';

    public string $value = '';

    public string $country = 'BR';

    public string $label = '';

    public bool $is_primary = false;

    public function rules(): array
    {
        $isPhone = ($this->contactType()?->isPhone()) === true;

        return [
            'person_id' => ['required', 'integer', 'exists:persons,id'],
            'type' => ['required', 'in:'.implode(',', array_map(fn ($c) => $c->value, PersonContactType::cases()))],
            'value' => ['required', 'string', 'max:255'],
            'country' => [
                $isPhone ? 'required' : 'nullable',
                'string',
                'in:'.implode(',', array_map(fn ($c) => $c->value, Country::cases())),
            ],
            'label' => ['nullable', 'string', 'max:64'],
            'is_primary' => ['boolean'],
        ];
    }

    public function setContact(PersonContact $contact): void
    {
        $this->contact = $contact;
        $this->person_id = $contact->person_id;
        $this->type = $contact->type?->value ?? PersonContactType::Phone->value;
        $this->country = $contact->country ?? 'BR';
        $this->label = $contact->label ?? '';
        $this->is_primary = $contact->is_primary;

        // Strip any "+CC " prefix from the stored value so the local portion is
        // what the user sees in the masked input. The prefix is re-added in
        // save() based on the (possibly changed) country selection.
        $value = (string) $contact->value;
        if ($contact->type?->isPhone() && $contact->country) {
            $prefix = '+'.Country::from($contact->country)->phoneCode().' ';
            if (str_starts_with($value, $prefix)) {
                $value = substr($value, strlen($prefix));
            }
        }
        $this->value = $value;
    }

    public function save(): PersonContact
    {
        $data = $this->validate();
        if (($data['label'] ?? null) === '') {
            $data['label'] = null;
        }

        // For phone-shaped contacts, store the value already prefixed with the
        // country calling code so reads don't have to recombine the parts.
        // For non-phone types, drop the country (it's not meaningful).
        $type = PersonContactType::from($data['type']);
        if ($type->isPhone()) {
            $country = Country::from($data['country']);
            $data['value'] = self::formatInternational($data['value'], $country);
        } else {
            $data['country'] = null;
        }

        if ($this->contact) {
            $this->contact->update($data);
        } else {
            $this->contact = PersonContact::create($data);
        }

        // Demote any other primary contact of the same type for the same person.
        if ($this->contact->is_primary) {
            PersonContact::query()
                ->where('person_id', $this->contact->person_id)
                ->where('type', $this->contact->type)
                ->whereKeyNot($this->contact->id)
                ->update(['is_primary' => false]);
        }

        return $this->contact;
    }

    private function contactType(): ?PersonContactType
    {
        return PersonContactType::tryFrom($this->type);
    }

    /**
     * Combine the user-entered (already country-masked) phone value with the
     * E.164 calling code: "+55 (11) 99999-9999". An existing "+" prefix is
     * stripped first so changing the country updates the prefix on edit.
     */
    private static function formatInternational(string $value, Country $country): string
    {
        $value = trim($value);
        if ($value === '') {
            return $value;
        }

        // Drop any pre-existing "+<digits> " prefix and rebuild from the
        // current country, so a country change actually takes effect.
        $value = preg_replace('/^\+\d+\s+/', '', $value) ?? $value;

        return '+'.$country->phoneCode().' '.$value;
    }
}
