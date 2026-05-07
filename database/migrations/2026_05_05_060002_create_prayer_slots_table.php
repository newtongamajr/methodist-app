<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prayer_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prayer_schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedSmallInteger('capacity');
            $table->string('mode', 16);
            $table->timestamps();

            $table->unique(['prayer_schedule_id', 'starts_at']);
            $table->index(['church_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prayer_slots');
    }
};
