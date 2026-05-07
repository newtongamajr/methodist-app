<?php

use App\Enums\RegionKind;
use App\Models\EcclesiasticalRegion;
use Database\Seeders\EcclesiasticalRegionSeeder;

it('seeds the 8 regular regions plus REMA and REMNE missionary regions', function () {
    $this->seed(EcclesiasticalRegionSeeder::class);

    expect(EcclesiasticalRegion::count())->toBe(10);

    expect(EcclesiasticalRegion::where('kind', RegionKind::Regular)->count())->toBe(8);
    expect(EcclesiasticalRegion::where('kind', RegionKind::Missionary)->pluck('code')->all())
        ->toEqualCanonicalizing(['REMA', 'REMNE']);

    expect(EcclesiasticalRegion::where('code', 'RE1')->first()->name)->toBe('1ª Região Eclesiástica');
});

it('is idempotent when re-seeded', function () {
    $this->seed(EcclesiasticalRegionSeeder::class);
    $this->seed(EcclesiasticalRegionSeeder::class);

    expect(EcclesiasticalRegion::count())->toBe(10);
});
