<?php

namespace Database\Seeders;

use App\Enums\FastingRestriction;
use App\Enums\FastingType;
use App\Models\FastingCampaign;
use Illuminate\Database\Seeder;

class FastingCampaignSeeder extends Seeder
{
    public function run(): void
    {
        FastingCampaign::updateOrCreate(
            ['slug' => 'jejum-oracao-2026-05'],
            [
                'name' => '2026 — Jejum e Oração Nacional Metodista',
                'description' => 'Campanha nacional de jejum e oração da Igreja Metodista no Brasil — 2026.',
                'start_date' => '2026-05-04',
                'end_date' => '2026-05-24',
                'types' => array_map(fn ($t) => $t->value, FastingType::cases()),
                'restrictions' => array_map(fn ($r) => $r->value, FastingRestriction::cases()),
                'is_active' => true,
            ],
        );
    }
}
