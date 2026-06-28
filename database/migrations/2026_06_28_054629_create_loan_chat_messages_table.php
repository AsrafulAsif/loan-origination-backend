<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loan_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_application_id');
            $table->text('message');
            $table->string('message_type');

            $table->string('from_employee_id')->nullable();
            $table->string('from_employee_name')->nullable();

            $table->string('from_stage')->nullable();
            $table->string('from_stage_name')->nullable();

            $table->unsignedBigInteger('reply_to')->nullable();

            $table->boolean('is_active')->default(true);

            $table->json('created_by')->nullable();
            $table->json('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_chat_messages');
    }
};
