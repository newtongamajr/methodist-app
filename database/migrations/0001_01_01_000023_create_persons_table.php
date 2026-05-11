<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated `persons` table — alpha-01 baseline.
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
CREATE TABLE `persons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `person_type` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'individual',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `preferred_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_id` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_id_type` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `gender` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marital_status` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `natures` json DEFAULT NULL,
  `additional_data` json DEFAULT NULL,
  `managing_church_id` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `persons_tax_id_unique` (`tax_id`),
  KEY `persons_name_index` (`name`),
  KEY `persons_birthdate_index` (`birthdate`),
  KEY `persons_managing_church_id_index` (`managing_church_id`),
  CONSTRAINT `persons_managing_church_id_foreign` FOREIGN KEY (`managing_church_id`) REFERENCES `churches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::statement('DROP TABLE IF EXISTS `persons`');
        Schema::enableForeignKeyConstraints();
    }
};
