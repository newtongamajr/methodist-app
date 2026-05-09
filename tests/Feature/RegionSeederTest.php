<?php

use App\Enums\RegionKind;
use App\Models\EcclesiasticalRegion;
use Database\Seeders\EcclesiasticalRegionSeeder;

it('seeds the National HQ + 8 regular regions + REMA and REMNE missionary regions', function () {
    $this->seed(EcclesiasticalRegionSeeder::class);

    expect(EcclesiasticalRegion::count())->toBe(11);

    expect(EcclesiasticalRegion::where('kind', RegionKind::NationalHeadquarters)->pluck('code')->all())
        ->toBe(['SEDE']);
    expect(EcclesiasticalRegion::where('kind', RegionKind::Regular)->count())->toBe(8);
    expect(EcclesiasticalRegion::where('kind', RegionKind::Missionary)->pluck('code')->all())
        ->toEqualCanonicalizing(['REMA', 'REMNE']);

    expect(EcclesiasticalRegion::where('code', 'RE1')->first()->name)->toBe('1ª Região Eclesiástica');
});

it('is idempotent when re-seeded', function () {
    $this->seed(EcclesiasticalRegionSeeder::class);
    $this->seed(EcclesiasticalRegionSeeder::class);

    expect(EcclesiasticalRegion::count())->toBe(11);
});

it('links every seeded region to an Organization-type Person with the right nature', function () {
    $this->seed(EcclesiasticalRegionSeeder::class);

    $sede = EcclesiasticalRegion::where('code', 'SEDE')->first();
    expect($sede->person)->not->toBeNull();
    expect($sede->person->person_type->value)->toBe('organization');
    expect($sede->person->natures)->toBe(['national_headquarters']);

    $re1 = EcclesiasticalRegion::where('code', 'RE1')->first();
    expect($re1->person)->not->toBeNull();
    expect($re1->person->person_type->value)->toBe('organization');
    expect($re1->person->natures)->toBe(['ecclesiastical_region']);
});
