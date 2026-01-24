<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_payments', function (Blueprint $table) {
            $table->string('customer_name', 255)->nullable()->after('credit_logicalref');
            $table->string('customer_phone', 50)->nullable()->after('customer_name');
            $table->string('customer_passport', 50)->nullable()->after('customer_phone');
            $table->string('customer_contract', 50)->nullable()->after('customer_passport');
            $table->string('branch', 50)->nullable()->after('customer_contract');

            $table->index(['customer_contract']);
            $table->index(['customer_phone']);
            $table->index(['customer_passport']);
            $table->index(['branch']);
        });
    }

    public function down(): void
    {
        Schema::table('credit_payments', function (Blueprint $table) {
            $table->dropIndex(['customer_contract']);
            $table->dropIndex(['customer_phone']);
            $table->dropIndex(['customer_passport']);
            $table->dropIndex(['branch']);

            $table->dropColumn([
                'customer_name',
                'customer_phone',
                'customer_passport',
                'customer_contract',
                'branch',
            ]);
        });
    }
};
