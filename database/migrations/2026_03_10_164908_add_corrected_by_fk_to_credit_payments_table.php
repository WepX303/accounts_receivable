<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('credit_payments', function (Blueprint $table) {

            $table->foreign('corrected_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('credit_payments', function (Blueprint $table) {

            $table->dropForeign(['corrected_by']);
        });
    }
};
