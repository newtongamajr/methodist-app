<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_embeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 16);
            $table->string('url', 2048);
            $table->string('title')->nullable();
            $table->string('thumbnail_url', 2048)->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['post_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_embeds');
    }
};
