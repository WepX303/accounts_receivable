<?php

namespace App\Services\Credits;

use App\Models\Credit;
use Illuminate\Support\Facades\DB;

class CreditsResyncAmountLocalService
{
    public function run(): array
    {
        $query = Credit::query()
            ->whereRaw('ROUND(COALESCE(amount_local, 0)::numeric, 2) <> ROUND(COALESCE(amount, 0)::numeric, 2)');

        $affectedCount = (clone $query)->count();

        if ($affectedCount === 0) {
            return [
                'success' => true,
                'affected_count' => 0,
                'updated_count' => 0,
                'message' => 'All records are already in sync.',
            ];
        }

        $updatedCount = $query->update([
            'amount_local' => DB::raw('amount'),
            'updated_at' => now(),
        ]);

        return [
            'success' => true,
            'affected_count' => $affectedCount,
            'updated_count' => $updatedCount,
            'message' => 'amount_local values were successfully synced with amount.',
        ];
    }
}