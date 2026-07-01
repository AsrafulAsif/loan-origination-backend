<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('file_manager') || Schema::hasColumn('file_manager', 'loan_id')) {
            return;
        }

        Schema::table('file_manager', function (Blueprint $table) {
            $table->string('loan_id')->nullable()->after('id')->index();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('file_manager') || !Schema::hasColumn('file_manager', 'loan_id')) {
            return;
        }

        Schema::table('file_manager', function (Blueprint $table) {
            $table->dropColumn('loan_id');
        });
    }
};
