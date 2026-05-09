<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('pastor_assignments');
        Schema::dropIfExists('pastors');
    }

    public function down(): void
    {
        // Tables intentionally not recreated; their schema and data are
        // superseded by persons + person_role_assignments in this batch.
    }
};
