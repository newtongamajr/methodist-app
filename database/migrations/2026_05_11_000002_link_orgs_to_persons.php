<?php

use App\Enums\PersonNature;
use App\Enums\PersonType;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add nullable person_id to all three org tables.
        Schema::table('ecclesiastical_regions', function (Blueprint $table) {
            $table->foreignId('person_id')->nullable()->after('id')->constrained('persons')->restrictOnDelete();
        });
        Schema::table('districts', function (Blueprint $table) {
            $table->foreignId('person_id')->nullable()->after('id')->constrained('persons')->restrictOnDelete();
        });
        Schema::table('churches', function (Blueprint $table) {
            $table->foreignId('person_id')->nullable()->after('id')->constrained('persons')->restrictOnDelete();
        });

        // 2. Backfill: one Org Person per existing org row, name copied over.
        $now = now();
        $this->backfill('ecclesiastical_regions', PersonNature::EcclesiasticalRegion->value, $now);
        $this->backfill('districts', PersonNature::District->value, $now);
        $this->backfill('churches', PersonNature::Church->value, $now);

        // 3. Make person_id NOT NULL + unique (1:1 with the org row).
        Schema::table('ecclesiastical_regions', function (Blueprint $table) {
            $table->foreignId('person_id')->nullable(false)->change();
            $table->unique('person_id', 'ecclesiastical_regions_person_id_unique');
        });
        Schema::table('districts', function (Blueprint $table) {
            $table->foreignId('person_id')->nullable(false)->change();
            $table->unique('person_id', 'districts_person_id_unique');
        });
        Schema::table('churches', function (Blueprint $table) {
            $table->foreignId('person_id')->nullable(false)->change();
            $table->unique('person_id', 'churches_person_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('ecclesiastical_regions', function (Blueprint $table) {
            $table->dropUnique('ecclesiastical_regions_person_id_unique');
            $table->dropConstrainedForeignId('person_id');
        });
        Schema::table('districts', function (Blueprint $table) {
            $table->dropUnique('districts_person_id_unique');
            $table->dropConstrainedForeignId('person_id');
        });
        Schema::table('churches', function (Blueprint $table) {
            $table->dropUnique('churches_person_id_unique');
            $table->dropConstrainedForeignId('person_id');
        });
    }

    private function backfill(string $table, string $nature, Carbon $now): void
    {
        DB::table($table)->whereNull('person_id')->orderBy('id')->each(function (object $row) use ($table, $nature, $now) {
            $personId = DB::table('persons')->insertGetId([
                'person_type' => PersonType::Organization->value,
                'name' => $row->name ?? "{$table}#{$row->id}",
                'natures' => json_encode([$nature]),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table($table)->where('id', $row->id)->update(['person_id' => $personId]);
        });
    }
};
