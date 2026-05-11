<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated `users` table — alpha-01 baseline.
 *
 * The whole chain of historical migrations was squashed into one
 * migration per table at the alpha cut. FKs are kept inline; FK checks
 * are toggled off / on around the DDL so the run order doesn't have to
 * resolve the circular references (persons ↔ churches ↔ regions and
 * the post_scopes / church_user fan-outs).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::statement(<<<'SQL'
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `person_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pt_BR',
  `appearance` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_person_id_unique` (`person_id`),
  CONSTRAINT `users_person_id_foreign` FOREIGN KEY (`person_id`) REFERENCES `persons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::statement('DROP TABLE IF EXISTS `users`');
        Schema::enableForeignKeyConstraints();
    }
};
