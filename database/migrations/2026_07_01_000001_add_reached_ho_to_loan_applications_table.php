<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('loan_applications') || Schema::hasColumn('loan_applications', 'reached_ho')) {
            return;
        }

        Schema::table('loan_applications', function (Blueprint $table) {
            $table->boolean('reached_ho')->default(false)->after('current_workflow_stage_id')->index();
        });

        if (Schema::hasTable('workflow_stages')) {
            DB::table('loan_applications')
                ->whereIn('current_workflow_stage_id', function ($query) {
                    $query->select('id')
                        ->from('workflow_stages')
                        ->whereIn('stage_type', ['HO', 'ho']);
                })
                ->update(['reached_ho' => true]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('loan_applications') || !Schema::hasColumn('loan_applications', 'reached_ho')) {
            return;
        }

        Schema::table('loan_applications', function (Blueprint $table) {
            $table->dropColumn('reached_ho');
        });
    }
};
