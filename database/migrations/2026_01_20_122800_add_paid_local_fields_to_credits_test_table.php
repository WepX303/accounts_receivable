<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credits_test', function (Blueprint $table) {
            $table->decimal('paid_local', 18, 2)->nullable()->after('paid');
            $table->unsignedBigInteger('paid_updated_by')->nullable()->after('paid_local');
            $table->timestamp('paid_updated_at')->nullable()->after('paid_updated_by');
            $table->text('paid_note')->nullable()->after('paid_updated_at');

            $table->foreign('paid_updated_by')->references('id')->on('users')->nullOnDelete();

            // pratik index
            $table->index(['paid_updated_at']);
        });
    }

    public function down(): void
    {
        Schema::table('credits_test', function (Blueprint $table) {
            $table->dropForeign(['paid_updated_by']);
            $table->dropIndex(['paid_updated_at']);
            $table->dropColumn(['paid_local', 'paid_updated_by', 'paid_updated_at', 'paid_note']);
        });
    }
};
