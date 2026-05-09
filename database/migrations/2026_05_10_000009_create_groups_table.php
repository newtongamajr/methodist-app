<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 16);
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->foreignId('ecclesiastical_region_id')
                ->nullable()
                ->constrained('ecclesiastical_regions')
                ->nullOnDelete();
            $table->foreignId('district_id')
                ->nullable()
                ->constrained('districts')
                ->nullOnDelete();
            $table->foreignId('church_id')
                ->nullable()
                ->constrained('churches')
                ->nullOnDelete();
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['kind', 'is_active']);
            $table->index(['ecclesiastical_region_id', 'kind']);
            $table->index(['district_id', 'kind']);
            $table->index(['church_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
