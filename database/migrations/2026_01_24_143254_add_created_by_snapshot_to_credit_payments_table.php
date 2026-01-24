<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('credit_payments', function (Blueprint $table) {
            $table->string('created_by_name', 255)->nullable()->after('created_by');
            $table->string('created_by_email', 255)->nullable()->after('created_by_name');
            $table->string('created_by_phone', 50)->nullable()->after('created_by_email');

            $table->index('created_by_name', 'cp_created_by_name_idx');
            $table->index('created_by_email', 'cp_created_by_email_idx');
            $table->index('created_by_phone', 'cp_created_by_phone_idx');
        });
    }

    public function down(): void
    {
        Schema::table('credit_payments', function (Blueprint $table) {
            $table->dropIndex('cp_created_by_name_idx');
            $table->dropIndex('cp_created_by_email_idx');
            $table->dropIndex('cp_created_by_phone_idx');

            $table->dropColumn([
                'created_by_name',
                'created_by_email',
                'created_by_phone',
            ]);
        });
    }
};
