<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('credit_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('corrected_by')->nullable()->after('voided_by');

            $table->dateTime('corrected_at')->nullable()->after('corrected_by');

            $table->string('correct_reason', 300)->nullable()->after('corrected_at');

            $table->index('corrected_by');
            $table->index('corrected_at');
        });
    }

    public function down(): void
    {
        Schema::table('credit_payments', function (Blueprint $table) {
            $table->dropIndex(['corrected_by']);
            $table->dropIndex(['corrected_at']);

            $table->dropColumn(['corrected_by', 'corrected_at', 'correct_reason']);
        });
    }
};