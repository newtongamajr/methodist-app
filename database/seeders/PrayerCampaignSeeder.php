<?php

namespace Database\Seeders;

use App\Models\PrayerCampaign;
use Illuminate\Database\Seeder;

class PrayerCampaignSeeder extends Seeder
{
    public function run(): void
    {
        PrayerCampaign::updateOrCreate(
            ['slug' => 'oracao-2026-05'],
            [
                'name' => '2026 — Oração Nacional Metodista',
                'description' => 'Campanha nacional de oração da Igreja Metodista no Brasil — 2026.',
                'objectives' => "Buscar a Deus em unidade através da oração contínua durante o período da campanha. \n\n• Cobertura horária ininterrupta com intercessores em cada igreja participante. \n• Renovação espiritual dos membros, líderes e pastores. \n• Avivamento das comunidades locais. \n• Multiplicação de discípulos e plantação de novas congregações.",
                'start_date' => '2026-05-04',
                'end_date' => '2026-05-24',
                'is_active' => true,
            ],
        );
    }
}
