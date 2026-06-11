<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_payments', function (Blueprint $table) {
            $table->index(['credit_logicalref', 'id'], 'cp_credit_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('credit_payments', function (Blueprint $table) {
            $table->dropIndex('cp_credit_id_idx');
        });
    }
};
