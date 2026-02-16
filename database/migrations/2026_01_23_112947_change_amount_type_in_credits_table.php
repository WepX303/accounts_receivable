<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credits', function (Blueprint $table) {
            $table->decimal('amount', 18, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('credits', function (Blueprint $table) {
            $table->double('amount')->nullable()->change();
        });
    }
};
