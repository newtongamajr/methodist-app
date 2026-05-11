<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\EcclesiasticalRegion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDistrictSeeder extends Seeder
{
    public function run(): void
    {
        EcclesiasticalRegion::all()->each(function (EcclesiasticalRegion $region): void {
            foreach (['Norte', 'Sul', 'Centro'] as $i => $name) {
                $full = "Distrito {$name} — {$region->code}";
                District::updateOrCreate(
                    [
                        'ecclesiastical_region_id' => $region->id,
                        'slug' => Str::slug("{$region->code}-{$name}"),
                    ],
                    [
                        'name' => $full,
                        'display_order' => $i,
                        'is_active' => true,
                    ],
                );
            }
        });
    }
}
