<?php

use App\Models\PrayerCampaign;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public ?PrayerCampaign $campaign = null;

    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public string $objectives = '';
    public string $start_date = '';
    public string $end_date = '';
    public bool $is_active = true;

    public function mount(?int $campaignId = null): void
    {
        abort_unless(auth()->user()?->can('prayer.schedule.manage'), 403);

        if ($campaignId) {
            $this->campaign = PrayerCampaign::findOrFail($campaignId);
            $this->name = $this->campaign->name;
            $this->slug = $this->campaign->slug;
            $this->description = $this->campaign->description ?? '';
            $this->objectives = $this->campaign->objectives ?? '';
            $this->start_date = $this->campaign->start_date->format('Y-m-d');
            $this->end_date = $this->campaign->end_date->format('Y-m-d');
            $this->is_active = $this->campaign->is_active;
        } else {
            $this->start_date = now()->toDateString();
            $this->end_date = now()->addWeeks(3)->toDateString();
        }
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('prayer_campaigns', 'slug')->ignore($this->campaign?->id)],
            'description' => ['nullable', 'string', 'max:5000'],
            'objectives' => ['nullable', 'string', 'max:5000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_active' => ['boolean'],
        ]);

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

        session()->flash('status', __('Campaign saved.'));

        $this->redirect(route('admin.prayer-campaigns.edit', $this->campaign), navigate: true);
    }
};