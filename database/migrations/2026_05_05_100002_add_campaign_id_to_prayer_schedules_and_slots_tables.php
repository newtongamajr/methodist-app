<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prayer_schedules', function (Blueprint $table) {
            $table->foreignId('prayer_campaign_id')
                ->nullable()
                ->after('church_id')
                ->constrained('prayer_campaigns')
                ->nullOnDelete();
            $table->index(['prayer_campaign_id', 'date']);
        });

        Schema::table('prayer_slots', function (Blueprint $table) {
            $table->foreignId('prayer_campaign_id')
                ->nullable()
                ->after('church_id')
                ->constrained('prayer_campaigns')
                ->nullOnDelete();
            $table->index(['prayer_campaign_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::table('prayer_slots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prayer_campaign_id');
        });
        Schema::table('prayer_schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prayer_campaign_id');
        });
    }
};
