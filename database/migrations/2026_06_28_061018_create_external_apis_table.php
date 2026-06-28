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
            $table->string('api_name');
            $table->string('api_code')->unique();
            $table->string('api_base_url');
            $table->string('api_method', 10); // GET, POST, PUT, DELETE, etc.

            $table->json('param_template')->nullable();
            $table->json('request_template')->nullable();
            $table->json('response_template')->nullable();
            $table->json('headers_template')->nullable();

            $table->json('created_by')->nullable();
            $table->json('updated_by')->nullable();

            $table->boolean('is_active')->default(true);

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
