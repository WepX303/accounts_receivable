<?php

namespace App\Http\Controllers\Api\V1\Commands;

use App\Http\Controllers\Admin\Settings\CommandCenterController;
use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Support\ApiResponse;
use App\Support\Panel;

/**
 * The command centre, run by the panel's own controller.
 */
class CommandController extends Controller
{
    /**
     * Command key => the panel action that runs it.
     */
    public const ACTIONS = [
        'clear_all_caches' => 'clearAllCaches',
        'credits_resync_amount_local' => 'resyncAmountLocal',
        'sync_credits' => 'syncCredits',
        'sync_avshocrecat' => 'syncAvshocrecat',
    ];

    public function index()
    {
        $commands = Panel::call(CommandCenterController::class, 'index')->getData()['commands'];

        return ApiResponse::success([
            // Without the panel's own addresses; the app runs a command by its key.
            'commands' => array_map(fn (array $command) => array_diff_key($command, ['route' => 0, 'method' => 0]), $commands),
        ]);
    }

    public function run(string $command, AuditLogger $audit)
    {
        $response = Panel::call(CommandCenterController::class, self::ACTIONS[$command]);
        $refusal = Panel::refusal($response);

        $audit->log(
            action: 'command_run',
            category: 'system',
            extra: ['command' => $command],
            message: 'Command run from the mobile app',
            isSuccess: $refusal === null,
            severity: 'warning'
        );

        return $refusal ?? ApiResponse::success(null, Panel::flashed($response, 'success'));
    }
}
