<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Models\PrayerCampaign;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Form;

class PrayerCampaignForm extends Form
{
    public ?PrayerCampaign $campaign = null;

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public string $objectives = '';

    public string $start_date = '';

    public string $end_date = '';

    public bool $is_active = true;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('prayer_campaigns', 'slug')->ignore($this->campaign?->id)],
            'description' => ['nullable', 'string', 'max:5000'],
            'objectives' => ['nullable', 'string', 'max:5000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_active' => ['boolean'],
        ];
    }

    public function setCampaign(PrayerCampaign $campaign): void
    {
        $this->campaign = $campaign;
        $this->name = $campaign->name;
        $this->slug = $campaign->slug;
        $this->description = $campaign->description ?? '';
        $this->objectives = $campaign->objectives ?? '';
        $this->start_date = $campaign->start_date->format('Y-m-d');
        $this->end_date = $campaign->end_date->format('Y-m-d');
        $this->is_active = $campaign->is_active;
    }

    public function save(): PrayerCampaign
    {
        $data = $this->validate();

        if (empty($data['slug'])) {
            $base = Str::slug($data['name']) ?: 'campaign-'.Str::lower(Str::random(6));
            $slug = $base;
            $i = 1;
            while (PrayerCampaign::query()->where('slug', $slug)->where('id', '!=', $this->campaign?->id ?? 0)->exists()) {
                $slug = $base.'-'.(++$i);
            }
            $data['slug'] = $slug;
        }

        if ($this->campaign) {
            $this->campaign->update($data);
        } else {
            $this->campaign = PrayerCampaign::create($data);
        }

        return $this->campaign;
    }
}
