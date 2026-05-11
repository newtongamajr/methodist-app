<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated `prayer_slots` table — alpha-01 baseline.
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
CREATE TABLE `prayer_slots` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `prayer_schedule_id` bigint unsigned NOT NULL,
  `church_id` bigint unsigned NOT NULL,
  `prayer_campaign_id` bigint unsigned DEFAULT NULL,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `capacity` smallint unsigned NOT NULL,
  `mode` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prayer_slots_prayer_schedule_id_starts_at_unique` (`prayer_schedule_id`,`starts_at`),
  KEY `prayer_slots_church_id_starts_at_index` (`church_id`,`starts_at`),
  KEY `prayer_slots_prayer_campaign_id_starts_at_index` (`prayer_campaign_id`,`starts_at`),
  CONSTRAINT `prayer_slots_church_id_foreign` FOREIGN KEY (`church_id`) REFERENCES `churches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prayer_slots_prayer_campaign_id_foreign` FOREIGN KEY (`prayer_campaign_id`) REFERENCES `prayer_campaigns` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prayer_slots_prayer_schedule_id_foreign` FOREIGN KEY (`prayer_schedule_id`) REFERENCES `prayer_schedules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=115 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::statement('DROP TABLE IF EXISTS `prayer_slots`');
        Schema::enableForeignKeyConstraints();
    }
};
