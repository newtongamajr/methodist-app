<?php

namespace Database\Seeders;

use App\Enums\ChurchType;
use App\Enums\PastorRole;
use App\Models\Church;
use App\Models\EcclesiasticalRegion;
use App\Models\Pastor;
use App\Models\PastorAssignment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoChurchSeeder extends Seeder
{
    public function run(): void
    {
        EcclesiasticalRegion::all()->each(function (EcclesiasticalRegion $region): void {
            $type = $region->kind->value === 'missionary'
                ? ChurchType::MissionaryPoint->value
                : ChurchType::Church->value;

            $church = Church::factory()->create([
                'ecclesiastical_region_id' => $region->id,
                'type' => $type,
                'name' => ($type === 'missionary_point' ? 'Ponto Missionário Demo ' : 'Igreja Metodista Demo ').$region->code,
                'slug' => Str::slug('demo-'.$region->code),
            ]);

            $main = Pastor::factory()->create();
            PastorAssignment::create([
                'pastor_id' => $main->id,
                'church_id' => $church->id,
                'role' => PastorRole::Main->value,
                'start_date' => now()->toDateString(),
                'display_order' => 0,
            ]);

            Pastor::factory()->count(2)->create()->each(function ($pastor, $i) use ($church) {
                PastorAssignment::create([
                    'pastor_id' => $pastor->id,
                    'church_id' => $church->id,
                    'role' => PastorRole::Auxiliary->value,
                    'start_date' => now()->toDateString(),
                    'display_order' => $i + 1,
                ]);
            });
        });
    }
}
