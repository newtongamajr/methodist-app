<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_role_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('persons')->cascadeOnDelete();
            $table->foreignId('function_id')->constrained('functions')->restrictOnDelete();
            $table->foreignId('assignment_role_id')
                ->nullable()
                ->constrained('assignment_roles')
                ->nullOnDelete();
            $table->foreignId('group_id')
                ->nullable()
                ->constrained('groups')
                ->cascadeOnDelete();
            $table->foreignId('church_id')
                ->nullable()
                ->constrained('churches')
                ->cascadeOnDelete();
            $table->foreignId('ecclesiastical_region_id')
                ->nullable()
                ->constrained('ecclesiastical_regions')
                ->cascadeOnDelete();
            $table->foreignId('district_id')
                ->nullable()
                ->constrained('districts')
                ->cascadeOnDelete();
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->json('context_data')->nullable();
            $table->timestamps();

            $table->index(['person_id', 'function_id'], 'pra_person_fn_idx');
            $table->index(['function_id', 'church_id'], 'pra_fn_church_idx');
            $table->index(['function_id', 'group_id'], 'pra_fn_group_idx');
            $table->index(['function_id', 'district_id'], 'pra_fn_district_idx');
            $table->index(['function_id', 'ecclesiastical_region_id'], 'pra_fn_region_idx');
            $table->index(['ended_at'], 'pra_ended_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_role_assignments');
    }
};
