<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('person_contacts', function (Blueprint $table) {
            // ISO 3166-1 alpha-2 country code. Only meaningful for phone-shaped
            // contact types (phone / mobile / whatsapp); null for email,
            // social, website, and pre-existing rows that haven't been edited.
            $table->string('country', 2)->nullable()->after('value');
        });
    }

    public function down(): void
    {
        Schema::table('person_contacts', function (Blueprint $table) {
            $table->dropColumn('country');
        });
    }
};
