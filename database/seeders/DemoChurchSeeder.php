<?php

namespace Database\Seeders;

use App\Enums\ChurchType;
use App\Enums\PersonNature;
use App\Enums\PersonType;
use App\Models\Church;
use App\Models\EcclesiasticalRegion;
use App\Models\FunctionRole;
use App\Models\Person;
use App\Models\PersonRoleAssignment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoChurchSeeder extends Seeder
{
    public function run(): void
    {
        $mainPastorFn = FunctionRole::query()->where('slug', 'main_pastor')->first();
        $auxPastorFn = FunctionRole::query()->where('slug', 'auxiliary_pastor')->first();

        EcclesiasticalRegion::all()->each(function (EcclesiasticalRegion $region) use ($mainPastorFn, $auxPastorFn): void {
            $type = $region->kind->value === 'missionary'
                ? ChurchType::MissionaryPoint->value
                : ChurchType::Church->value;

            $church = Church::factory()->create([
                'ecclesiastical_region_id' => $region->id,
                'type' => $type,
                'name' => ($type === 'missionary_point' ? 'Ponto Missionário Demo ' : 'Igreja Metodista Demo ').$region->code,
                'slug' => Str::slug('demo-'.$region->code),
            ]);

            if ($mainPastorFn) {
                $main = Person::create([
                    'person_type' => PersonType::Individual->value,
                    'name' => 'Pr. '.fake()->name(),
                    'natures' => [PersonNature::Pastor->value],
                    'managing_church_id' => $church->id,
                ]);
                PersonRoleAssignment::create([
                    'person_id' => $main->id,
                    'function_id' => $mainPastorFn->id,
                    'church_id' => $church->id,
                    'started_at' => now()->toDateString(),
                    'is_primary' => true,
                ]);
            }

            if ($auxPastorFn) {
                for ($i = 0; $i < 2; $i++) {
                    $aux = Person::create([
                        'person_type' => PersonType::Individual->value,
                        'name' => 'Pr. '.fake()->name(),
                        'natures' => [PersonNature::Pastor->value],
                        'managing_church_id' => $church->id,
                    ]);
                    PersonRoleAssignment::create([
                        'person_id' => $aux->id,
                        'function_id' => $auxPastorFn->id,
                        'church_id' => $church->id,
                        'started_at' => now()->toDateString(),
                    ]);
                }
            }
        });
    }
}
