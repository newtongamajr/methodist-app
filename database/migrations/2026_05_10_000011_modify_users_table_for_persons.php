<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('church_id');
            $table->dropColumn(['member_type', 'phone', 'birthdate']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('person_id')
                ->after('id')
                ->constrained('persons')
                ->cascadeOnDelete();
            $table->unique('person_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('person_id');
            $table->string('member_type', 16)->default('member');
            $table->string('phone', 32)->nullable();
            $table->date('birthdate')->nullable();
            $table->foreignId('church_id')->nullable()->constrained('churches')->nullOnDelete();
        });
    }
};
