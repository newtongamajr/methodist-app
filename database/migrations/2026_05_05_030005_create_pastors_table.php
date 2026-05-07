<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pastors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('role', 16)->default('auxiliary');
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['church_id', 'role', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pastors');
    }
};
