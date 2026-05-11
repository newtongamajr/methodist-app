<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Models\District;
use Illuminate\Validation\Rule;
use Livewire\Form;

class DistrictForm extends Form
{
    public ?District $district = null;

    public ?int $ecclesiastical_region_id = null;

    public string $name = '';

    public string $slug = '';

    public string $code = '';

    public int $display_order = 0;

    public bool $is_active = true;

    public function rules(): array
    {
        return [
            'ecclesiastical_region_id' => ['required', 'integer', 'exists:ecclesiastical_regions,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:255',
                Rule::unique('districts', 'slug')
                    ->where('ecclesiastical_region_id', $this->ecclesiastical_region_id)
                    ->ignore($this->district?->id),
            ],
            'code' => ['nullable', 'string', 'max:32'],
            'display_order' => ['integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ];
    }

    public function setDistrict(District $district): void
    {
        $this->district = $district;
        $this->ecclesiastical_region_id = $district->ecclesiastical_region_id;
        $this->name = $district->name;
        $this->slug = $district->slug;
        $this->code = $district->code ?? '';
        $this->display_order = $district->display_order;
        $this->is_active = $district->is_active;
    }
}
