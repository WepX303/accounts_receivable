<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('credits_test', function (Blueprint $table) {
            $table->bigInteger('logicalref')->primary();

            $table->string('branch')->nullable();
            $table->text('name')->nullable();
            $table->string('passport')->nullable();
            $table->string('phone')->nullable();
            $table->string('contract')->nullable();

            $table->timestamp('date_')->nullable();

            $table->double('amount')->nullable();
            $table->decimal('paid', 18, 2)->nullable();

            $table->timestamp('willpaiddate')->nullable();
            $table->integer('willpaidamount')->nullable();

            $table->text('note')->nullable();
            $table->timestamp('lastnoteddate')->nullable();

            $table->string('status')->nullable();
            $table->boolean('active')->default(true);

            $table->integer('initiator_i')->nullable();
            $table->string('clientref')->nullable();
            $table->string('custstatus')->nullable();
            $table->string('assurance')->nullable();
            $table->integer('ctype')->nullable();
            $table->string('cardno')->nullable();
            $table->string('fishno')->nullable();
            $table->string('manager')->nullable();
            $table->string('confirmedby')->nullable();
            $table->string('gstatus')->nullable();

            $table->bigInteger('rv_bigint')->default(0)->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credits_test');
    }
};
