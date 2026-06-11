<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('credit_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('corrected_from_payment_id')->nullable()->after('correct_reason');
            $table->index('corrected_from_payment_id');
        });
    }

    public function down(): void
    {
        Schema::table('credit_payments', function (Blueprint $table) {
            $table->dropIndex(['corrected_from_payment_id']);
            $table->dropColumn('corrected_from_payment_id');
        });
    }
};