<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add person_id so prayer-slot signups can be recorded for a Person who is
     * not the User entering them — mirrors the act-as wiring already used for
     * fasting entries. The unique constraint moves from (slot, user) to
     * (slot, person) since a slot is filled per-Person, not per-User.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('prayer_signups', 'person_id')) {
            Schema::table('prayer_signups', function (Blueprint $table) {
                $table->foreignId('person_id')->nullable()->after('user_id')
                    ->constrained('persons')->cascadeOnDelete();
            });
        }

        DB::statement('
            UPDATE prayer_signups ps
            INNER JOIN users u ON u.id = ps.user_id
            SET ps.person_id = u.person_id
            WHERE ps.person_id IS NULL
        ');

        $indexes = collect(DB::select('SHOW INDEX FROM prayer_signups'))->pluck('Key_name')->all();

        if (in_array('prayer_signups_prayer_slot_id_user_id_unique', $indexes, true)) {
            // Both user_id and prayer_slot_id have FKs that lean on the
            // composite unique we're about to drop. Lay down dedicated
            // indexes first, ignoring the composite (which can't support an
            // FK after we drop it).
            Schema::table('prayer_signups', function (Blueprint $table) use ($indexes) {
                if (! in_array('prayer_signups_user_id_status_index', $indexes, true)
                    && ! in_array('prayer_signups_user_id_index', $indexes, true)) {
                    $table->index('user_id');
                }
                if (! in_array('prayer_signups_prayer_slot_id_index', $indexes, true)) {
                    $table->index('prayer_slot_id');
                }
            });

            Schema::table('prayer_signups', function (Blueprint $table) {
                $table->dropUnique('prayer_signups_prayer_slot_id_user_id_unique');
            });
        }

        if (! in_array('prayer_signups_prayer_slot_id_person_id_unique', $indexes, true)) {
            Schema::table('prayer_signups', function (Blueprint $table) {
                $table->unique(['prayer_slot_id', 'person_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('prayer_signups', function (Blueprint $table) {
            $table->dropUnique(['prayer_slot_id', 'person_id']);
            $table->dropConstrainedForeignId('person_id');
            $table->unique(['prayer_slot_id', 'user_id'], 'prayer_signups_prayer_slot_id_user_id_unique');
        });
    }
};
