<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per audience the post is published to. A single Post can have
     * multiple scope rows so a regional admin who manages two regions can
     * publish once with two scope rows. Visibility is OR across the rows:
     * any matching row makes the post visible to that user.
     *
     * Allowed shapes:
     *   - national_post=true, all FKs null  → everyone sees it
     *   - region_id only                    → users whose church lives in that region
     *   - region_id + district_id           → users whose church lives in that district
     *   - all three filled                  → users on that specific church
     */
    public function up(): void
    {
        Schema::create('post_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->boolean('national_post')->default(false);
            $table->foreignId('region_id')->nullable()->constrained('ecclesiastical_regions')->cascadeOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('districts')->cascadeOnDelete();
            $table->foreignId('church_id')->nullable()->constrained('churches')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['post_id', 'national_post']);
            $table->index(['region_id', 'district_id', 'church_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_scopes');
    }
};
