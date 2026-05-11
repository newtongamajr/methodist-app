<?php

namespace Database\Seeders;

use App\Enums\PersonNature;
use App\Enums\PersonType;
use App\Enums\RegionKind;
use App\Models\EcclesiasticalRegion;
use App\Models\Person;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EcclesiasticalRegionSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            // National HQ — rendered/managed in the same Regions UI but flagged
            // distinctly via RegionKind::NationalHeadquarters. There's only one.
            ['code' => 'SEDE', 'name' => 'Sede Nacional', 'kind' => RegionKind::NationalHeadquarters, 'order' => 0, 'nature' => PersonNature::NationalHeadquarters],
            ['code' => 'RE1', 'name' => '1ª Região Eclesiástica', 'kind' => RegionKind::Regular, 'order' => 1, 'nature' => PersonNature::EcclesiasticalRegion],
            ['code' => 'RE2', 'name' => '2ª Região Eclesiástica', 'kind' => RegionKind::Regular, 'order' => 2, 'nature' => PersonNature::EcclesiasticalRegion],
            ['code' => 'RE3', 'name' => '3ª Região Eclesiástica', 'kind' => RegionKind::Regular, 'order' => 3, 'nature' => PersonNature::EcclesiasticalRegion],
            ['code' => 'RE4', 'name' => '4ª Região Eclesiástica', 'kind' => RegionKind::Regular, 'order' => 4, 'nature' => PersonNature::EcclesiasticalRegion],
            ['code' => 'RE5', 'name' => '5ª Região Eclesiástica', 'kind' => RegionKind::Regular, 'order' => 5, 'nature' => PersonNature::EcclesiasticalRegion],
            ['code' => 'RE6', 'name' => '6ª Região Eclesiástica', 'kind' => RegionKind::Regular, 'order' => 6, 'nature' => PersonNature::EcclesiasticalRegion],
            ['code' => 'RE7', 'name' => '7ª Região Eclesiástica', 'kind' => RegionKind::Regular, 'order' => 7, 'nature' => PersonNature::EcclesiasticalRegion],
            ['code' => 'RE8', 'name' => '8ª Região Eclesiástica', 'kind' => RegionKind::Regular, 'order' => 8, 'nature' => PersonNature::EcclesiasticalRegion],
            ['code' => 'REMA', 'name' => 'Região Missionária da Amazônia', 'kind' => RegionKind::Missionary, 'order' => 9, 'nature' => PersonNature::EcclesiasticalRegion],
            ['code' => 'REMNE', 'name' => 'Região Missionária do Nordeste', 'kind' => RegionKind::Missionary, 'order' => 10, 'nature' => PersonNature::EcclesiasticalRegion],
        ];

        foreach ($regions as $r) {
            DB::transaction(function () use ($r) {
                $region = EcclesiasticalRegion::query()->where('code', $r['code'])->first();

                if ($region) {
                    $region->update([
                        'name' => $r['name'],
                        'kind' => $r['kind']->value,
                        'display_order' => $r['order'],
                    ]);
                    $region->person?->update(['name' => $r['name']]);

                    return;
                }

                $person = Person::create([
                    'person_type' => PersonType::Organization->value,
                    'name' => $r['name'],
                    'natures' => [$r['nature']->value],
                ]);

                EcclesiasticalRegion::create([
                    'person_id' => $person->id,
                    'code' => $r['code'],
                    'name' => $r['name'],
                    'kind' => $r['kind']->value,
                    'display_order' => $r['order'],
                ]);
            });
        }
    }
}
