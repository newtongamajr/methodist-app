<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecclesiastical_region_id')
                ->constrained('ecclesiastical_regions')
                ->restrictOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('code', 32)->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['ecclesiastical_region_id', 'slug']);
            $table->index(['ecclesiastical_region_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};
