<?php

namespace Database\Seeders;

use App\Enums\PersonNature;
use App\Enums\PersonType;
use App\Enums\RegionKind;
use App\Models\District;
use App\Models\EcclesiasticalRegion;
use App\Models\Person;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoDistrictSeeder extends Seeder
{
    public function run(): void
    {
        // National HQ has no districts under it — skip.
        EcclesiasticalRegion::query()
            ->where('kind', '!=', RegionKind::NationalHeadquarters->value)
            ->get()
            ->each(function (EcclesiasticalRegion $region): void {
                foreach (['Norte', 'Sul', 'Centro'] as $i => $name) {
                    $full = "Distrito {$name} — {$region->code}";
                    $slug = Str::slug("{$region->code}-{$name}");

                    DB::transaction(function () use ($region, $slug, $full, $i) {
                        $existing = District::query()
                            ->where('ecclesiastical_region_id', $region->id)
                            ->where('slug', $slug)
                            ->first();

                        if ($existing) {
                            $existing->update(['name' => $full, 'display_order' => $i, 'is_active' => true]);
                            $existing->person?->update(['name' => $full]);

                            return;
                        }

                        $person = Person::create([
                            'person_type' => PersonType::Organization->value,
                            'name' => $full,
                            'natures' => [PersonNature::District->value],
                        ]);

                        District::create([
                            'person_id' => $person->id,
                            'ecclesiastical_region_id' => $region->id,
                            'name' => $full,
                            'slug' => $slug,
                            'display_order' => $i,
                            'is_active' => true,
                        ]);
                    });
                }
            });
    }
}
