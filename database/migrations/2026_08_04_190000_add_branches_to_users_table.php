<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Branches a user is allowed to see. NULL or an empty array means every
     * branch, which keeps every existing account working exactly as before.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('branches')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('branches');
        });
    }
};
