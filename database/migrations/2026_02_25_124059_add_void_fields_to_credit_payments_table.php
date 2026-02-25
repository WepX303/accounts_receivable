<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_payments', function (Blueprint $table) {
            $table->timestamp('voided_at')->nullable()->after('created_at');
            $table->unsignedBigInteger('voided_by')->nullable()->after('voided_at');
            $table->text('void_reason')->nullable()->after('voided_by');

            $table->foreign('voided_by')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->index(['voided_at']);
            $table->index(['voided_by']);
        });
    }

    public function down(): void
    {
        Schema::table('credit_payments', function (Blueprint $table) {
            $table->dropForeign(['voided_by']);
            $table->dropIndex(['voided_at']);
            $table->dropIndex(['voided_by']);

            $table->dropColumn(['voided_at', 'voided_by', 'void_reason']);
        });
    }
};