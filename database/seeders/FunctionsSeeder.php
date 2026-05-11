<?php

namespace Database\Seeders;

use App\Enums\FunctionAppliesTo;
use App\Models\FunctionRole;
use Illuminate\Database\Seeder;

class FunctionsSeeder extends Seeder
{
    public function run(): void
    {
        $functions = [
            // Admin scopes
            ['national_admin', 'National Admin', [FunctionAppliesTo::Admin->value], null, 10],
            ['regional_admin', 'Regional Admin', [FunctionAppliesTo::Admin->value], null, 20],
            ['district_admin', 'District Admin', [FunctionAppliesTo::Admin->value], null, 30],
            ['local_admin', 'Local Admin', [FunctionAppliesTo::Admin->value], null, 40],

            // Pastoral
            ['main_pastor', 'Main Pastor', [FunctionAppliesTo::Pastor->value], 1, 100],
            ['auxiliary_pastor', 'Auxiliary Pastor', [FunctionAppliesTo::Pastor->value], null, 110],
            ['seminarist', 'Seminarist', [FunctionAppliesTo::Pastor->value], null, 120],

            // Group functions
            ['lead', 'Lead', [
                FunctionAppliesTo::Council->value,
                FunctionAppliesTo::Ministry->value,
                FunctionAppliesTo::Commission->value,
            ], 1, 200],
            ['co_lead', 'Co-Lead', [
                FunctionAppliesTo::Council->value,
                FunctionAppliesTo::Ministry->value,
                FunctionAppliesTo::Commission->value,
            ], null, 210],
            ['secretary', 'Secretary', [
                FunctionAppliesTo::Council->value,
                FunctionAppliesTo::Ministry->value,
                FunctionAppliesTo::Commission->value,
            ], 1, 220],
            ['treasurer', 'Treasurer', [
                FunctionAppliesTo::Council->value,
                FunctionAppliesTo::Ministry->value,
                FunctionAppliesTo::Commission->value,
            ], 1, 230],
            ['member', 'Member', [
                FunctionAppliesTo::Council->value,
                FunctionAppliesTo::Ministry->value,
                FunctionAppliesTo::Commission->value,
            ], null, 240],
            ['adviser', 'Adviser', [
                FunctionAppliesTo::Council->value,
                FunctionAppliesTo::Ministry->value,
                FunctionAppliesTo::Commission->value,
            ], null, 250],
        ];

        foreach ($functions as [$slug, $name, $appliesTo, $maxHolders, $order]) {
            FunctionRole::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'applies_to' => $appliesTo,
                    'max_holders' => $maxHolders,
                    'is_active' => true,
                    'display_order' => $order,
                ],
            );
        }
    }
}
