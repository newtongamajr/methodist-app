<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prayer_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('slot_minutes')->default(60);
            $table->unsignedSmallInteger('capacity_per_slot')->default(5);
            $table->string('mode', 16)->default('presential');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['church_id', 'date', 'start_time']);
            $table->index(['church_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prayer_schedules');
    }
};
