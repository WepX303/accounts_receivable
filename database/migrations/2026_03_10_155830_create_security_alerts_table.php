<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('security_alerts', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('user_id')->nullable();

            $table->string('alert_type', 100); // multiple_failed_login, payment_voided, ip_changed
            $table->string('risk_level', 20)->default('medium'); // low, medium, high, critical
            $table->boolean('is_resolved')->default(false);

            $table->string('ip_address', 64)->nullable();
            $table->text('message')->nullable();
            $table->json('meta')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();

            $table->index('user_id');
            $table->index('alert_type');
            $table->index('risk_level');
            $table->index('is_resolved');
            $table->index('created_at');

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_alerts');
    }
};