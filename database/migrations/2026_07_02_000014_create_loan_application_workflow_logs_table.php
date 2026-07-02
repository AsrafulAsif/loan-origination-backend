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
CREATE TABLE IF NOT EXISTS `loan_application_workflow_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `loan_application_id` bigint(20) unsigned NOT NULL,
  `from_stage_id` bigint(20) unsigned NOT NULL,
  `to_stage_id` bigint(20) unsigned DEFAULT NULL,
  `stage_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_by` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `action_at` datetime NOT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `send_back_config` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `revert_pending` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_application_workflow_logs');
    }
};
