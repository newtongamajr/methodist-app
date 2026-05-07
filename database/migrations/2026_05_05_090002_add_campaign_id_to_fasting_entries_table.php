<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fasting_entries', function (Blueprint $table) {
            $table->foreignId('fasting_campaign_id')
                ->nullable()
                ->after('user_id')
                ->constrained('fasting_campaigns')
                ->nullOnDelete();

            $table->index(['fasting_campaign_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('fasting_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fasting_campaign_id');
        });
    }
};
