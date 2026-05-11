<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated `post_embeds` table — alpha-01 baseline.
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
CREATE TABLE `post_embeds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint unsigned NOT NULL,
  `provider` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(2048) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thumbnail_url` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `post_embeds_post_id_display_order_index` (`post_id`,`display_order`),
  CONSTRAINT `post_embeds_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::statement('DROP TABLE IF EXISTS `post_embeds`');
        Schema::enableForeignKeyConstraints();
    }
};
