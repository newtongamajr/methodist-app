<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated `post_likes` table — alpha-01 baseline.
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
CREATE TABLE `post_likes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `person_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `post_likes_post_id_person_id_unique` (`post_id`,`person_id`),
  KEY `post_likes_user_id_foreign` (`user_id`),
  KEY `post_likes_person_id_foreign` (`person_id`),
  KEY `post_likes_post_id_index` (`post_id`),
  CONSTRAINT `post_likes_person_id_foreign` FOREIGN KEY (`person_id`) REFERENCES `persons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `post_likes_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `post_likes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::statement('DROP TABLE IF EXISTS `post_likes`');
        Schema::enableForeignKeyConstraints();
    }
};
