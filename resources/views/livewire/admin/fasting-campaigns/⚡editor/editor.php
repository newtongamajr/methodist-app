<?php

use App\Enums\FastingRestriction;
use App\Enums\FastingType;
use App\Models\FastingCampaign;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
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

    public function mount(?int $campaignId = null): void
    {
        abort_unless(auth()->user()?->can('fasting.calendar.manage'), 403);

        if ($campaignId) {
            $this->campaign = FastingCampaign::findOrFail($campaignId);
            $this->name = $this->campaign->name;
            $this->slug = $this->campaign->slug;
            $this->description = $this->campaign->description ?? '';
            $this->start_date = $this->campaign->start_date->format('Y-m-d');
            $this->end_date = $this->campaign->end_date->format('Y-m-d');
            $this->types = $this->campaign->types ?? [];
            $this->restrictions = $this->campaign->restrictions ?? [];
            $this->is_active = $this->campaign->is_active;
        } else {
            $this->types = array_map(fn ($t) => $t->value, FastingType::cases());
            $this->restrictions = array_map(fn ($r) => $r->value, FastingRestriction::cases());
            $this->start_date = now()->toDateString();
            $this->end_date = now()->addWeeks(3)->toDateString();
        }
    }

    public function save(): void
    {
        $data = $this->validate([
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
        ]);

        if (empty($data['slug'])) {
            $base = Str::slug($data['name']) ?: 'campaign-'.Str::lower(Str::random(6));
            $slug = $base;
            $i = 1;
            while (FastingCampaign::query()->where('slug', $slug)->where('id', '!=', $this->campaign?->id ?? 0)->exists()) {
                $slug = $base.'-'.(++$i);
            }
            $data['slug'] = $slug;
        }

        if ($this->campaign) {
            $this->campaign->update($data);
        } else {
            $this->campaign = FastingCampaign::create($data);
        }

        session()->flash('status', __('Campaign saved.'));

        $this->redirect(route('admin.fasting-campaigns.edit', $this->campaign), navigate: true);
    }
};