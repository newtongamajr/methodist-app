<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('persons')->cascadeOnDelete();
            $table->foreignId('related_person_id')->constrained('persons')->cascadeOnDelete();
            $table->string('relationship_type', 32);
            $table->string('inverse_type', 32);
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->json('context_data')->nullable();
            $table->timestamps();

            $table->unique(['person_id', 'related_person_id', 'relationship_type'], 'pr_unique_triple');
            $table->index(['related_person_id', 'relationship_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_relationships');
    }
};
