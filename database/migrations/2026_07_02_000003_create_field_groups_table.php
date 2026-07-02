<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS `field_groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `section_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_order` int(11) NOT NULL DEFAULT 0,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `layout` enum('card','inline','bordered','plain') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'card',
  `columns` int(11) DEFAULT NULL,
  `gap` enum('none','sm','md','lg') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `repeatable` tinyint(1) NOT NULL DEFAULT 0,
  `min_instances` int(11) DEFAULT NULL,
  `max_instances` int(11) DEFAULT NULL,
  `add_button_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remove_button_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instance_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conditional_logic` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`conditional_logic`)),
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `class_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collapsible` tinyint(1) NOT NULL DEFAULT 0,
  `default_collapsed` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `section_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=176 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('field_groups');
    }
};
