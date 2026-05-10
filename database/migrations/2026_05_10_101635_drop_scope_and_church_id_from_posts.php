<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the binary `scope = shared|local` + nullable `church_id`
     * model with a multi-row `post_scopes` table. The single existing post
     * is purged per agreement so we don't need a backfill — every future
     * post writes into post_scopes from the start.
     */
    public function up(): void
    {
        // Wipe the lone existing post (and its dependent rows) before
        // dropping the columns so the FK on post_scopes can stay clean.
        DB::table('post_likes')->delete();
        DB::table('post_comments')->delete();
        DB::table('post_embeds')->delete();
        DB::table('post_media')->delete();
        DB::table('posts')->delete();

        // Drop the FK before the composite index so MySQL doesn't reject
        // the index drop ("needed in a foreign key constraint").
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['church_id']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['church_id', 'status', 'published_at']);
            $table->dropColumn(['church_id', 'scope']);
            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['status', 'published_at']);
            $table->string('scope', 16)->default('shared')->after('author_id');
            $table->foreignId('church_id')->nullable()->after('scope')->constrained()->nullOnDelete();
            $table->index(['church_id', 'status', 'published_at']);
        });
    }
};
