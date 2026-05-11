<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a `person_id` column so a fast can be recorded for a Person who is
     * not (necessarily) the User entering it. Drives the act-as flow:
     * `user_id` keeps recording who saved the row; `person_id` says whose fast
     * it is. The (user_id, date) unique index is replaced by (person_id, date)
     * since uniqueness is per-Person, not per-User.
     *
     * Idempotent so a half-applied prior run can be retried cleanly.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('fasting_entries', 'person_id')) {
            Schema::table('fasting_entries', function (Blueprint $table) {
                $table->foreignId('person_id')->nullable()->after('user_id')
                    ->constrained('persons')->cascadeOnDelete();
            });
        }

        // Backfill from the recording user's linked Person whenever blank.
        DB::statement('
            UPDATE fasting_entries fe
            INNER JOIN users u ON u.id = fe.user_id
            SET fe.person_id = u.person_id
            WHERE fe.person_id IS NULL
        ');

        $indexes = collect(DB::select('SHOW INDEX FROM fasting_entries'))->pluck('Key_name')->all();

        if (in_array('fasting_entries_user_id_date_unique', $indexes, true)) {
            // The user_id FK relies on an index — add a plain one before
            // dropping the unique so MySQL doesn't refuse the drop.
            if (! in_array('fasting_entries_user_id_index', $indexes, true)) {
                Schema::table('fasting_entries', function (Blueprint $table) {
                    $table->index('user_id');
                });
            }
            Schema::table('fasting_entries', function (Blueprint $table) {
                $table->dropUnique('fasting_entries_user_id_date_unique');
            });
        }

        if (! in_array('fasting_entries_person_id_date_unique', $indexes, true)) {
            Schema::table('fasting_entries', function (Blueprint $table) {
                $table->unique(['person_id', 'date']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('fasting_entries', function (Blueprint $table) {
            $table->dropUnique(['person_id', 'date']);
            $table->dropConstrainedForeignId('person_id');
            $table->unique(['user_id', 'date'], 'fasting_entries_user_id_date_unique');
        });
    }
};
