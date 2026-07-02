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
CREATE TABLE IF NOT EXISTS `loan_chat_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `loan_application_id` bigint(20) unsigned NOT NULL,
  `message` text DEFAULT NULL,
  `message_type` enum('TEXT','FILE') NOT NULL DEFAULT 'TEXT',
  `from_employee_id` varchar(255) DEFAULT NULL,
  `from_stage` varchar(100) DEFAULT NULL,
  `reply_to` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_by` varchar(255) DEFAULT NULL,
  `from_employee_name` varchar(255) DEFAULT NULL,
  `from_stage_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8mb4;
SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_chat_messages');
    }
};
