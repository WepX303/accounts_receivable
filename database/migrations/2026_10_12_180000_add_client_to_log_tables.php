<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['activity_logs', 'login_histories', 'security_alerts'];

    /**
     * Where an entry came from: the panel ("web"), the app ("mobile") or a
     * scheduled command ("system"). Earlier rows stay empty: it was not recorded.
     */
    public function up(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->string('client', 20)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn('client');
            });
        }
    }
};
