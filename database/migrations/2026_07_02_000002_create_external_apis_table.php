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
CREATE TABLE IF NOT EXISTS `external_apis` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `api_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_base_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `param_template` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_template` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `response_template` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `headers_template` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`created_by`)),
  `updated_by` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`updated_by`)),
  `is_active` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `external_apis_api_code_unique` (`api_code`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_apis');
    }
};
