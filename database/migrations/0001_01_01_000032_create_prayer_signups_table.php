<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated `prayer_signups` table — alpha-01 baseline.
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
CREATE TABLE `prayer_signups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `prayer_slot_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `person_id` bigint unsigned DEFAULT NULL,
  `status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'confirmed',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prayer_signups_prayer_slot_id_person_id_unique` (`prayer_slot_id`,`person_id`),
  KEY `prayer_signups_user_id_status_index` (`user_id`,`status`),
  KEY `prayer_signups_person_id_foreign` (`person_id`),
  KEY `prayer_signups_prayer_slot_id_index` (`prayer_slot_id`),
  CONSTRAINT `prayer_signups_person_id_foreign` FOREIGN KEY (`person_id`) REFERENCES `persons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prayer_signups_prayer_slot_id_foreign` FOREIGN KEY (`prayer_slot_id`) REFERENCES `prayer_slots` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prayer_signups_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::statement('DROP TABLE IF EXISTS `prayer_signups`');
        Schema::enableForeignKeyConstraints();
    }
};
