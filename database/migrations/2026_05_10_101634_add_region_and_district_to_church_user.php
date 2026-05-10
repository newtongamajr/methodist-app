<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extend church_user from "user-to-church membership" into a 4-shape
     * scope table that doubles as the source of truth for regional/district
     * admin grants. Allowed shapes per row:
     *
     *   - region only          → regional admin scope on that region
     *   - region + district    → district admin scope on that district
     *   - all three filled     → local admin scope on that church OR plain
     *                            membership (distinguished by role)
     *
     * National admins keep ZERO rows (their access is implied by the role).
     * The unique key is widened to (user_id, region_id, district_id, church_id)
     * so a user can hold multiple non-overlapping scopes simultaneously.
     */
    public function up(): void
    {
        Schema::table('church_user', function (Blueprint $table) {
            if (! Schema::hasColumn('church_user', 'region_id')) {
                $table->foreignId('region_id')->nullable()->after('church_id')
                    ->constrained('ecclesiastical_regions')->nullOnDelete();
            }
            if (! Schema::hasColumn('church_user', 'district_id')) {
                $table->foreignId('district_id')->nullable()->after('region_id')
                    ->constrained('districts')->nullOnDelete();
            }
        });

        // Backfill region/district from each row's existing church so the
        // shape stays consistent (all three filled = local/membership).
        DB::statement('
            UPDATE church_user cu
            INNER JOIN churches c ON c.id = cu.church_id
            SET cu.district_id = c.district_id,
                cu.region_id = c.ecclesiastical_region_id
            WHERE cu.church_id IS NOT NULL
        ');

        $indexes = collect(DB::select('SHOW INDEX FROM church_user'))->pluck('Key_name')->all();

        if (in_array('church_user_church_id_user_id_unique', $indexes, true)) {
            // Add a plain index on church_id BEFORE dropping the composite
            // so the FK keeps a supporting index and MySQL doesn't refuse.
            if (! in_array('church_user_church_id_index', $indexes, true)) {
                Schema::table('church_user', function (Blueprint $table) {
                    $table->index('church_id');
                });
            }
            Schema::table('church_user', function (Blueprint $table) {
                $table->dropUnique('church_user_church_id_user_id_unique');
            });
        }

        // Make church_id nullable so regional/district admins can have rows
        // without a specific church. Laravel 12 supports change() natively.
        Schema::table('church_user', function (Blueprint $table) {
            $table->foreignId('church_id')->nullable()->change();
        });

        $indexes = collect(DB::select('SHOW INDEX FROM church_user'))->pluck('Key_name')->all();
        if (! in_array('church_user_user_scope_unique', $indexes, true)) {
            Schema::table('church_user', function (Blueprint $table) {
                // Per-shape uniqueness: same user can't hold the exact same
                // scope twice. NULLs are distinct in MySQL unique
                // constraints, so app logic still has to dedupe.
                $table->unique(['user_id', 'region_id', 'district_id', 'church_id'], 'church_user_user_scope_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('church_user', function (Blueprint $table) {
            $table->dropUnique('church_user_user_scope_unique');
            $table->foreignId('church_id')->nullable(false)->change();
            $table->unique(['church_id', 'user_id']);
            $table->dropConstrainedForeignId('district_id');
            $table->dropConstrainedForeignId('region_id');
        });
    }
};
