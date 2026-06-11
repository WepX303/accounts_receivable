<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_payments', function (Blueprint $table) {
            $table->string('receiver_phone_number', 30)->nullable()->after('phone_amount');
            $table->index('receiver_phone_number');
        });
    }

    public function down(): void
    {
        Schema::table('credit_payments', function (Blueprint $table) {
            $table->dropIndex(['receiver_phone_number']);
            $table->dropColumn('receiver_phone_number');
        });
    }
};
