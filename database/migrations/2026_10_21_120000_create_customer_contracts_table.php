<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The şertnamas made in the panel. Each row keeps everything that was
     * printed, so a contract prints the same way years later. It refers to the
     * credit it was filled from only by its source id, without a foreign key:
     * contracts never change or hold back anything in credits.
     */
    public function up(): void
    {
        Schema::create('customer_contracts', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('number', 30)->unique(); // UT-2026-0000001
            $table->string('status', 20)->default('draft'); // draft, approved, cancelled
            $table->string('template_version', 10);
            $table->string('branch', 50);

            // The credit the form was filled from; empty when filled by hand.
            $table->unsignedBigInteger('credit_source_id')->nullable();

            $table->date('contract_date');

            // Buyer as printed
            $table->string('customer_name', 255);
            $table->string('passport_series', 20)->nullable();
            $table->string('passport_number', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 100)->nullable();

            // Section 3, as the cashier wrote it
            $table->unsignedSmallInteger('months')->nullable();
            $table->decimal('monthly_amount', 18, 2)->nullable();
            $table->string('installment_part', 100)->nullable();

            $table->json('goods')->nullable();   // page 2 table rows
            $table->json('history')->nullable(); // the buyer's earlier credits on page 2

            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name', 255)->nullable();

            $table->unsignedBigInteger('approved_by')->nullable();
            $table->string('approved_by_name', 255)->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->string('cancelled_by_name', 255)->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();

            $table->unsignedInteger('print_count')->default(0);
            $table->timestamp('last_printed_at')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('branch');
            $table->index('credit_source_id');
            $table->index('passport_number');
            $table->index('contract_date');
            $table->index('created_by');

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('cancelled_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_contracts');
    }
};
