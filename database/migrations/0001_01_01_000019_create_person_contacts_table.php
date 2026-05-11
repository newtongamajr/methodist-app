<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated `person_contacts` table — alpha-01 baseline.
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
CREATE TABLE `person_contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `person_id` bigint unsigned NOT NULL,
  `type` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `country` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `person_contacts_person_id_type_index` (`person_id`,`type`),
  KEY `person_contacts_person_id_is_primary_index` (`person_id`,`is_primary`),
  CONSTRAINT `person_contacts_person_id_foreign` FOREIGN KEY (`person_id`) REFERENCES `persons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::statement('DROP TABLE IF EXISTS `person_contacts`');
        Schema::enableForeignKeyConstraints();
    }
};
