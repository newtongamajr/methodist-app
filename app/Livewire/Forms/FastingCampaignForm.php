<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\FastingRestriction;
use App\Enums\FastingType;
use App\Models\FastingCampaign;
use App\Support\GenerateUniqueSlug;
use Illuminate\Validation\Rule;
use Livewire\Form;

class FastingCampaignForm extends Form
{
    public ?FastingCampaign $campaign = null;

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public string $start_date = '';

    public string $end_date = '';

    public array $types = [];

    public array $restrictions = [];

    public bool $is_active = true;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('fasting_campaigns', 'slug')->ignore($this->campaign?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'types' => ['array', 'min:1'],
            'types.*' => ['string', 'in:'.implode(',', FastingType::values())],
            'restrictions' => ['array'],
            'restrictions.*' => ['string', 'in:'.implode(',', FastingRestriction::values())],
            'is_active' => ['boolean'],
        ];
    }

    public function setCampaign(FastingCampaign $campaign): void
    {
        $this->campaign = $campaign;
        $this->name = $campaign->name;
        $this->slug = $campaign->slug;
        $this->description = $campaign->description ?? '';
        $this->start_date = $campaign->start_date->format('Y-m-d');
        $this->end_date = $campaign->end_date->format('Y-m-d');
        $this->types = $campaign->types ?? [];
        $this->restrictions = $campaign->restrictions ?? [];
        $this->is_active = $campaign->is_active;
    }

    public function save(): FastingCampaign
    {
        $data = $this->validate();

        if (empty($data['slug'])) {
            $data['slug'] = (new GenerateUniqueSlug)(
                $data['name'],
                FastingCampaign::query()->whereKeyNot($this->campaign?->id ?? 0),
            );
        }

        if ($this->campaign) {
            $this->campaign->update($data);
        } else {
            $this->campaign = FastingCampaign::create($data);
        }

        return $this->campaign;
    }
}
