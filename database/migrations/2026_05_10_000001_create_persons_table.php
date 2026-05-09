<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persons', function (Blueprint $table) {
            $table->id();
            $table->string('person_type', 16)->default('individual');
            $table->string('name');
            $table->string('preferred_name')->nullable();
            $table->string('tax_id', 32)->nullable();
            $table->string('tax_id_type', 8)->nullable();
            $table->date('birthdate')->nullable();
            $table->string('gender', 16)->nullable();
            $table->string('marital_status', 32)->nullable();
            $table->string('photo_path')->nullable();
            $table->json('natures')->nullable();
            $table->json('additional_data')->nullable();
            $table->foreignId('managing_church_id')
                ->nullable()
                ->constrained('churches')
                ->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('birthdate');
            $table->index('managing_church_id');
        });

        // Partial unique on tax_id ignoring NULLs. Use a raw statement so it
        // also works on SQLite (used in tests). MySQL handles multiple NULLs
        // in a UNIQUE index out of the box, so this is portable.
        Schema::table('persons', function (Blueprint $table) {
            $table->unique('tax_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persons');
    }
};
