<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('churches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecclesiastical_region_id')
                ->constrained('ecclesiastical_regions')
                ->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('zip', 16)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('timezone', 64)->default('America/Sao_Paulo');
            $table->unsignedSmallInteger('max_prayers_per_slot')->default(5);
            $table->string('default_mode', 16)->default('presential');
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'ecclesiastical_region_id']);
            $table->index(['state', 'city']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('churches');
    }
};
