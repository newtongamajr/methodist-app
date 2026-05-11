<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated `person_role_assignments` table — alpha-01 baseline.
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
CREATE TABLE `person_role_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `person_id` bigint unsigned NOT NULL,
  `function_id` bigint unsigned NOT NULL,
  `assignment_role_id` bigint unsigned DEFAULT NULL,
  `group_id` bigint unsigned DEFAULT NULL,
  `church_id` bigint unsigned DEFAULT NULL,
  `ecclesiastical_region_id` bigint unsigned DEFAULT NULL,
  `district_id` bigint unsigned DEFAULT NULL,
  `started_at` date DEFAULT NULL,
  `ended_at` date DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `context_data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `person_role_assignments_assignment_role_id_foreign` (`assignment_role_id`),
  KEY `person_role_assignments_group_id_foreign` (`group_id`),
  KEY `person_role_assignments_church_id_foreign` (`church_id`),
  KEY `person_role_assignments_ecclesiastical_region_id_foreign` (`ecclesiastical_region_id`),
  KEY `person_role_assignments_district_id_foreign` (`district_id`),
  KEY `pra_person_fn_idx` (`person_id`,`function_id`),
  KEY `pra_fn_church_idx` (`function_id`,`church_id`),
  KEY `pra_fn_group_idx` (`function_id`,`group_id`),
  KEY `pra_fn_district_idx` (`function_id`,`district_id`),
  KEY `pra_fn_region_idx` (`function_id`,`ecclesiastical_region_id`),
  KEY `pra_ended_at_idx` (`ended_at`),
  CONSTRAINT `person_role_assignments_assignment_role_id_foreign` FOREIGN KEY (`assignment_role_id`) REFERENCES `assignment_roles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `person_role_assignments_church_id_foreign` FOREIGN KEY (`church_id`) REFERENCES `churches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `person_role_assignments_district_id_foreign` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `person_role_assignments_ecclesiastical_region_id_foreign` FOREIGN KEY (`ecclesiastical_region_id`) REFERENCES `ecclesiastical_regions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `person_role_assignments_function_id_foreign` FOREIGN KEY (`function_id`) REFERENCES `functions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `person_role_assignments_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `person_role_assignments_person_id_foreign` FOREIGN KEY (`person_id`) REFERENCES `persons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::statement('DROP TABLE IF EXISTS `person_role_assignments`');
        Schema::enableForeignKeyConstraints();
    }
};
