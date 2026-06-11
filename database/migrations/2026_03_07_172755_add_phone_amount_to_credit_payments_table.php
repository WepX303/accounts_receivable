<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('credit_payments', function (Blueprint $table) {
            $table->decimal('phone_amount', 18, 2)->default(0)->after('card_amount');
        });
    }

    public function down(): void
    {
        Schema::table('credit_payments', function (Blueprint $table) {
            $table->dropColumn('phone_amount');
        });
    }
};