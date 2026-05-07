<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecclesiastical_regions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('name');
            $table->string('kind', 16)->default('regular');
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['kind', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecclesiastical_regions');
    }
};
