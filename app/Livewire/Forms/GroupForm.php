<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\GroupKind;
use App\Models\Group;
use Illuminate\Validation\Rule;
use Livewire\Form;

class GroupForm extends Form
{
    public ?Group $group = null;

    public string $kind = 'council';

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    /** national | region | district | church — drives which scope FK is required. */
    public string $level = 'church';

    public ?int $ecclesiastical_region_id = null;

    public ?int $district_id = null;

    public ?int $church_id = null;

    public string $started_at = '';

    public string $ended_at = '';

    public bool $is_active = true;

    public function rules(): array
    {
        return [
            'kind' => ['required', 'in:'.implode(',', array_map(fn ($c) => $c->value, GroupKind::cases()))],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:255',
                Rule::unique('groups', 'slug')
                    ->where('ecclesiastical_region_id', $this->ecclesiastical_region_id)
                    ->where('district_id', $this->district_id)
                    ->where('church_id', $this->church_id)
                    ->ignore($this->group?->id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'level' => ['required', 'in:national,region,district,church'],
            'ecclesiastical_region_id' => [
                in_array($this->level, ['region', 'district', 'church'], true) ? 'required' : 'nullable',
                'integer', 'exists:ecclesiastical_regions,id',
            ],
            'district_id' => [
                in_array($this->level, ['district', 'church'], true) ? 'required' : 'nullable',
                'integer', 'exists:districts,id',
            ],
            'church_id' => [
                $this->level === 'church' ? 'required' : 'nullable',
                'integer', 'exists:churches,id',
            ],
            'started_at' => ['nullable', 'date'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'is_active' => ['boolean'],
        ];
    }

    public function setGroup(Group $group): void
    {
        $this->group = $group;
        $this->kind = $group->kind?->value ?? GroupKind::Council->value;
        $this->name = $group->name;
        $this->slug = $group->slug;
        $this->description = $group->description ?? '';
        $this->level = $group->level();
        $this->ecclesiastical_region_id = $group->ecclesiastical_region_id;
        $this->district_id = $group->district_id;
        $this->church_id = $group->church_id;
        $this->started_at = $group->started_at?->format('Y-m-d') ?? '';
        $this->ended_at = $group->ended_at?->format('Y-m-d') ?? '';
        $this->is_active = $group->is_active;
    }

    public function save(): Group
    {
        $data = $this->validate();

        // Strip scope FKs that don't belong at the chosen level — the
        // GroupObserver guards against mixed combos but normalizing here
        // keeps the row tidy when the user toggled levels in the UI.
        if ($data['level'] === 'national') {
            $data['ecclesiastical_region_id'] = null;
            $data['district_id'] = null;
            $data['church_id'] = null;
        } elseif ($data['level'] === 'region') {
            $data['district_id'] = null;
            $data['church_id'] = null;
        } elseif ($data['level'] === 'district') {
            $data['church_id'] = null;
        }

        unset($data['level']);

        foreach (['description', 'started_at', 'ended_at', 'slug'] as $k) {
            if (($data[$k] ?? null) === '') {
                $data[$k] = null;
            }
        }

        if ($this->group) {
            $this->group->update($data);
        } else {
            $this->group = Group::create($data);
        }

        return $this->group;
    }
}
