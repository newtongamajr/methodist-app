<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pastor_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pastor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('role', 16)->default('auxiliary');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['pastor_id', 'start_date', 'end_date']);
            $table->index(['church_id', 'start_date', 'end_date']);
        });

        // Carry forward existing pastor → church links that lived on the old
        // pastors.church_id column. Today's date is used as the start of the
        // historical record; end_date stays null (still active).
        if (Schema::hasColumn('pastors', 'church_id')) {
            $today = now()->toDateString();

            DB::table('pastors')
                ->whereNotNull('church_id')
                ->orderBy('id')
                ->each(function ($pastor) use ($today) {
                    DB::table('pastor_assignments')->insert([
                        'pastor_id' => $pastor->id,
                        'church_id' => $pastor->church_id,
                        'role' => $pastor->role ?? 'auxiliary',
                        'start_date' => $today,
                        'end_date' => null,
                        'display_order' => $pastor->display_order ?? 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
        }

        Schema::table('pastors', function (Blueprint $table) {
            if (Schema::hasColumn('pastors', 'church_id')) {
                $table->dropConstrainedForeignId('church_id');
            }
            if (Schema::hasColumn('pastors', 'role')) {
                $table->dropColumn('role');
            }
            if (Schema::hasColumn('pastors', 'display_order')) {
                $table->dropColumn('display_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pastors', function (Blueprint $table) {
            $table->foreignId('church_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 16)->default('auxiliary')->after('phone');
            $table->unsignedSmallInteger('display_order')->default(0)->after('role');
        });

        Schema::dropIfExists('pastor_assignments');
    }
};
