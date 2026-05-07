<?php

namespace Database\Seeders;

use App\Enums\RegionKind;
use App\Models\EcclesiasticalRegion;
use Illuminate\Database\Seeder;

class EcclesiasticalRegionSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            ['code' => 'RE1', 'name' => '1ª Região Eclesiástica', 'kind' => RegionKind::Regular, 'order' => 1],
            ['code' => 'RE2', 'name' => '2ª Região Eclesiástica', 'kind' => RegionKind::Regular, 'order' => 2],
            ['code' => 'RE3', 'name' => '3ª Região Eclesiástica', 'kind' => RegionKind::Regular, 'order' => 3],
            ['code' => 'RE4', 'name' => '4ª Região Eclesiástica', 'kind' => RegionKind::Regular, 'order' => 4],
            ['code' => 'RE5', 'name' => '5ª Região Eclesiástica', 'kind' => RegionKind::Regular, 'order' => 5],
            ['code' => 'RE6', 'name' => '6ª Região Eclesiástica', 'kind' => RegionKind::Regular, 'order' => 6],
            ['code' => 'RE7', 'name' => '7ª Região Eclesiástica', 'kind' => RegionKind::Regular, 'order' => 7],
            ['code' => 'RE8', 'name' => '8ª Região Eclesiástica', 'kind' => RegionKind::Regular, 'order' => 8],
            ['code' => 'REMA', 'name' => 'Região Missionária da Amazônia', 'kind' => RegionKind::Missionary, 'order' => 9],
            ['code' => 'REMNE', 'name' => 'Região Missionária do Nordeste', 'kind' => RegionKind::Missionary, 'order' => 10],
        ];

        foreach ($regions as $r) {
            EcclesiasticalRegion::updateOrCreate(
                ['code' => $r['code']],
                [
                    'name' => $r['name'],
                    'kind' => $r['kind']->value,
                    'display_order' => $r['order'],
                ],
            );
        }
    }
}
