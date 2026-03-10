<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name', 255)->nullable();
            $table->string('user_email', 255)->nullable();
            $table->string('user_phone', 50)->nullable();
            $table->string('user_role', 50)->nullable();

            $table->string('action', 100); // payment_created, payment_voided, login_failed
            $table->string('category', 50)->nullable(); // auth, payment, user, credit, profile, export
            $table->string('severity', 20)->default('info'); // info, warning, critical
            $table->boolean('is_suspicious')->default(false);

            $table->string('subject_type', 255)->nullable(); // App\Models\CreditPayment
            $table->string('subject_id', 100)->nullable();

            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type', 50)->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('platform', 100)->nullable();

            $table->string('http_method', 10)->nullable();
            $table->text('url')->nullable();
            $table->string('route_name', 150)->nullable();

            $table->boolean('is_success')->default(true);
            $table->text('message')->nullable();

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('extra')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
            $table->index('action');
            $table->index('category');
            $table->index('severity');
            $table->index('is_suspicious');
            $table->index('ip_address');
            $table->index('subject_id');
            $table->index('created_at');

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};