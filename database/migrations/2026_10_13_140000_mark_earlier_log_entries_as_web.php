<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TABLES = ['activity_logs', 'login_histories', 'security_alerts'];

    /**
     * Entries written before their source was recorded all came from the
     * panel: there was nothing else to come from. They say so now, instead
     * of showing as unknown.
     */
    public function up(): void
    {
        foreach (self::TABLES as $table) {
            DB::table($table)->whereNull('client')->update(['client' => 'web']);
        }
    }

    /**
     * Which entries had no source can no longer be told apart from those
     * that were written as the panel's; nothing is taken back.
     */
    public function down(): void
    {
    }
};
