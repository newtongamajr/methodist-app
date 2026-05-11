<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wire act-as into Posts: post_likes and post_comments now track who the
     * row is FOR (person_id, the participant) alongside who SAVED the row
     * (user_id, the actor). When a parent uses the Act-as button to comment
     * or like under their child's name, both fields get filled — UI then
     * renders "Parent in the name of Child" so the audience sees both.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('post_likes', 'person_id')) {
            Schema::table('post_likes', function (Blueprint $table) {
                $table->foreignId('person_id')->nullable()->after('user_id')
                    ->constrained('persons')->cascadeOnDelete();
            });
        }
        DB::statement('
            UPDATE post_likes pl
            INNER JOIN users u ON u.id = pl.user_id
            SET pl.person_id = u.person_id
            WHERE pl.person_id IS NULL
        ');

        // Swap the unique from (post, user) to (post, person). Keep the FK
        // on user_id alive by adding its own index first.
        $likeIdx = collect(DB::select('SHOW INDEX FROM post_likes'))->pluck('Key_name')->all();
        if (in_array('post_likes_post_id_user_id_unique', $likeIdx, true)) {
            Schema::table('post_likes', function (Blueprint $table) use ($likeIdx) {
                if (! in_array('post_likes_post_id_index', $likeIdx, true)) {
                    $table->index('post_id');
                }
            });
            Schema::table('post_likes', function (Blueprint $table) {
                $table->dropUnique('post_likes_post_id_user_id_unique');
            });
        }
        if (! in_array('post_likes_post_id_person_id_unique', $likeIdx, true)) {
            Schema::table('post_likes', function (Blueprint $table) {
                $table->unique(['post_id', 'person_id']);
            });
        }

        if (! Schema::hasColumn('post_comments', 'person_id')) {
            Schema::table('post_comments', function (Blueprint $table) {
                $table->foreignId('person_id')->nullable()->after('user_id')
                    ->constrained('persons')->cascadeOnDelete();
            });
        }
        DB::statement('
            UPDATE post_comments pc
            INNER JOIN users u ON u.id = pc.user_id
            SET pc.person_id = u.person_id
            WHERE pc.person_id IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('post_likes', function (Blueprint $table) {
            $table->dropUnique(['post_id', 'person_id']);
            $table->dropConstrainedForeignId('person_id');
            $table->unique(['post_id', 'user_id'], 'post_likes_post_id_user_id_unique');
        });

        Schema::table('post_comments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('person_id');
        });
    }
};
