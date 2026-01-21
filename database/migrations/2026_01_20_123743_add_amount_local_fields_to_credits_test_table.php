<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('credits_test', function (Blueprint $table) {
            $table->decimal('amount_local', 18, 2)->nullable()->after('amount');
            $table->unsignedBigInteger('amount_updated_by')->nullable()->after('amount_local');
            $table->timestamp('amount_updated_at')->nullable()->after('amount_updated_by');
            $table->text('amount_note')->nullable()->after('amount_updated_at');

            $table->foreign('amount_updated_by')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->index(['amount_updated_at']);
        });
    }

    public function down(): void
    {
        Schema::table('credits_test', function (Blueprint $table) {
            $table->dropForeign(['amount_updated_by']);
            $table->dropIndex(['amount_updated_at']);
            $table->dropColumn([
                'amount_local',
                'amount_updated_by',
                'amount_updated_at',
                'amount_note'
            ]);
        });
    }
};
