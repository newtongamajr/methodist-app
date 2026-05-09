<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\ChurchType;
use App\Enums\LocationMode;
use App\Models\Church;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ChurchForm extends Form
{
    public ?Church $church = null;

    public ?int $ecclesiastical_region_id = null;

    public ?int $district_id = null;

    public string $type = 'church';

    public string $name = '';

    public string $slug = '';

    public string $address = '';

    public string $city = '';

    public string $state = '';

    public string $zip = '';

    public string $timezone = 'America/Sao_Paulo';

    public int $max_prayers_per_slot = 5;

    public string $default_mode = 'presential';

    public string $phone = '';

    public string $email = '';

    public bool $is_active = true;

    public string $master_name = '';

    public string $master_email = '';

    public string $master_phone = '';

    public string $master_password = '';

    public function rules(): array
    {
        $rules = [
            'ecclesiastical_region_id' => ['required', 'integer', 'exists:ecclesiastical_regions,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'type' => ['required', 'in:'.implode(',', ChurchType::values())],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('churches', 'slug')->ignore($this->church?->id)],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:2'],
            'zip' => ['nullable', 'string', 'max:16'],
            'timezone' => ['required', 'string', 'max:64'],
            'max_prayers_per_slot' => ['required', 'integer', 'min:1', 'max:200'],
            'default_mode' => ['required', 'in:'.implode(',', array_map(fn ($c) => $c->value, LocationMode::cases()))],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ];

        if ($this->church === null) {
            $rules += [
                'master_name' => ['required', 'string', 'max:255'],
                'master_email' => ['required', 'email', 'lowercase', 'max:255', 'unique:users,email'],
                'master_phone' => ['nullable', 'string', 'max:32'],
                'master_password' => ['required', 'string', 'min:8'],
            ];
        }

        return $rules;
    }

    public function setChurch(Church $church): void
    {
        $this->church = $church;
        $this->ecclesiastical_region_id = $church->ecclesiastical_region_id;
        $this->district_id = $church->district_id;
        $this->type = $church->type?->value ?? ChurchType::Church->value;
        $this->name = $church->name;
        $this->slug = $church->slug;
        $this->address = $church->address ?? '';
        $this->city = $church->city ?? '';
        $this->state = $church->state ?? '';
        $this->zip = $church->zip ?? '';
        $this->timezone = $church->timezone;
        $this->max_prayers_per_slot = $church->max_prayers_per_slot;
        $this->default_mode = $church->default_mode->value;
        $this->phone = $church->phone ?? '';
        $this->email = $church->email ?? '';
        $this->is_active = $church->is_active;
    }
}
