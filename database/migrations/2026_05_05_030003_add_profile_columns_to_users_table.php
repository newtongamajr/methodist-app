<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('member_type', 16)->default('member')->after('email');
            $table->foreignId('church_id')
                ->nullable()
                ->after('member_type')
                ->constrained('churches')
                ->nullOnDelete();
            $table->string('locale', 8)->default('pt_BR')->after('church_id');
            $table->string('phone', 32)->nullable()->after('locale');
            $table->date('birthdate')->nullable()->after('phone');

            $table->index(['member_type', 'church_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('church_id');
            $table->dropColumn(['member_type', 'locale', 'phone', 'birthdate']);
        });
    }
};
