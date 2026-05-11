<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('churches', function (Blueprint $table) {
            $table->string('code', 32)->nullable()->after('slug');
            $table->index(['ecclesiastical_region_id', 'code'], 'churches_region_code_idx');
        });
    }

    public function down(): void
    {
        Schema::table('churches', function (Blueprint $table) {
            $table->dropIndex('churches_region_code_idx');
            $table->dropColumn('code');
        });
    }
};
