<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecclesiastical_regions', function (Blueprint $table) {
            // Widen to fit RegionKind::NationalHeadquarters value (21 chars).
            // Restating every attribute that was on the original column so
            // Laravel's change() doesn't drop them (per the L12 migrations
            // rule).
            $table->string('kind', 32)->default('regular')->change();
        });
    }

    public function down(): void
    {
        Schema::table('ecclesiastical_regions', function (Blueprint $table) {
            $table->string('kind', 16)->default('regular')->change();
        });
    }
};
