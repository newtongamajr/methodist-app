<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated `prayer_schedules` table — alpha-01 baseline.
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
CREATE TABLE `prayer_schedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `church_id` bigint unsigned NOT NULL,
  `prayer_campaign_id` bigint unsigned DEFAULT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `slot_minutes` smallint unsigned NOT NULL DEFAULT '60',
  `capacity_per_slot` smallint unsigned NOT NULL DEFAULT '5',
  `mode` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'presential',
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prayer_schedules_church_id_date_start_time_unique` (`church_id`,`date`,`start_time`),
  KEY `prayer_schedules_church_id_date_index` (`church_id`,`date`),
  KEY `prayer_schedules_prayer_campaign_id_date_index` (`prayer_campaign_id`,`date`),
  CONSTRAINT `prayer_schedules_church_id_foreign` FOREIGN KEY (`church_id`) REFERENCES `churches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prayer_schedules_prayer_campaign_id_foreign` FOREIGN KEY (`prayer_campaign_id`) REFERENCES `prayer_campaigns` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::statement('DROP TABLE IF EXISTS `prayer_schedules`');
        Schema::enableForeignKeyConstraints();
    }
};
