<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('login_histories', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('user_id')->nullable();

            $table->string('login_value', 255)->nullable(); // email veya phone
            $table->string('status', 30); // success, failed, logout
            $table->string('fail_reason', 100)->nullable(); // wrong_password, user_not_found, inactive_account

            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type', 50)->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('platform', 100)->nullable();

            $table->boolean('is_suspicious')->default(false);
            $table->text('message')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
            $table->index('status');
            $table->index('fail_reason');
            $table->index('ip_address');
            $table->index('is_suspicious');
            $table->index('created_at');

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_histories');
    }
};