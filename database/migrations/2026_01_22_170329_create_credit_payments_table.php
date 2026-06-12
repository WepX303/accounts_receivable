<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('credit_payments', function (Blueprint $table) {
            $table->id();

            // credits.logicalref BIGINT (primary)
            $table->unsignedBigInteger('credit_logicalref')->index();
            $table->unsignedBigInteger('credit_source_id')->index();

            $table->decimal('pay_amount', 18, 2);
            $table->string('method', 10); // cash|card|mixed
            $table->decimal('cash_amount', 18, 2)->default(0);
            $table->decimal('card_amount', 18, 2)->default(0);

            $table->decimal('old_amount_local', 18, 2)->nullable();
            $table->decimal('new_amount_local', 18, 2)->nullable();

            $table->decimal('old_paid_local', 18, 2)->nullable();
            $table->decimal('new_paid_local', 18, 2)->nullable();

            $table->text('note')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();


            $table->foreign('credit_source_id')
                ->references('source_id')
                ->on('credits')
                ->cascadeOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_payments');
    }
};
