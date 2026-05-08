<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\RegionKind;
use App\Models\EcclesiasticalRegion;
use Illuminate\Validation\Rule;
use Livewire\Form;

class RegionForm extends Form
{
    public ?EcclesiasticalRegion $region = null;

    public string $code = '';

    public string $name = '';

    public string $kind = 'regular';

    public int $display_order = 0;

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:16', Rule::unique('ecclesiastical_regions', 'code')->ignore($this->region?->id)],
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['required', 'in:'.implode(',', array_map(fn ($c) => $c->value, RegionKind::cases()))],
            'display_order' => ['integer', 'min:0', 'max:9999'],
        ];
    }

    public function setRegion(EcclesiasticalRegion $region): void
    {
        $this->region = $region;
        $this->code = $region->code;
        $this->name = $region->name;
        $this->kind = $region->kind->value;
        $this->display_order = $region->display_order;
    }

    public function save(): EcclesiasticalRegion
    {
        $data = $this->validate();

        if ($this->region) {
            $this->region->update($data);
        } else {
            $this->region = EcclesiasticalRegion::create($data);
        }

        return $this->region;
    }
}
