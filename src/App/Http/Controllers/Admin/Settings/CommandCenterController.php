<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Services\Credits\CreditsResyncAmountLocalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;
use Throwable;

class CommandCenterController extends Controller
{
    public function index(): View
    {
        $commands = [
            [
                'key' => 'clear_all_caches',
                'title' => __('commands.commands.clear_all_caches.title'),
                'description' => __('commands.commands.clear_all_caches.description'),
                'details' => __('commands.commands.clear_all_caches.details'),
                'route' => route('clear-all-caches'),
                'method' => 'POST',
                'risk' => 'low',
                'artisan_command' => 'php artisan clear:all',
                'confirm_message' => __('commands.commands.clear_all_caches.confirm'),
            ],
            [
                'key' => 'credits_resync_amount_local',
                'title' => __('commands.commands.credits_resync_amount_local.title'),
                'description' => __('commands.commands.credits_resync_amount_local.description'),
                'details' => __('commands.commands.credits_resync_amount_local.details'),
                'route' => route('credits.resync-amount-local'),
                'method' => 'POST',
                'risk' => 'low',
                'artisan_command' => 'Service: CreditsResyncAmountLocalService::run()',
                'confirm_message' => __('commands.commands.credits_resync_amount_local.confirm'),
            ],
            [
                'key' => 'sync_credits',
                'title' => __('commands.commands.sync_credits.title'),
                'description' => __('commands.commands.sync_credits.description'),
                'details' => __('commands.commands.sync_credits.details'),
                'route' => route('sync-credits'),
                'method' => 'POST',
                'risk' => 'medium',
                'artisan_command' => 'php artisan sync:credits',
                'confirm_message' => __('commands.commands.sync_credits.confirm'),
            ],
            [
                'key' => 'sync_avshocrecat',
                'title' => __('commands.commands.sync_avshocrecat.title'),
                'description' => __('commands.commands.sync_avshocrecat.description'),
                'details' => __('commands.commands.sync_avshocrecat.details'),
                'route' => route('sync-avshocrecat'),
                'method' => 'POST',
                'risk' => 'medium',
                'artisan_command' => 'php artisan sync:avshocrecat',
                'confirm_message' => __('commands.commands.sync_avshocrecat.confirm'),
            ],
        ];

        return view('pages.admin.settings.commands.index', compact('commands'));
    }

    public function clearAllCaches(): RedirectResponse
    {
        Artisan::call('clear:all');

        return redirect()
            ->route('commands.index')
            ->with('success', __('commands.commands.clear_all_caches.success'));
    }

    public function resyncAmountLocal(CreditsResyncAmountLocalService $service): RedirectResponse
    {
        $result = $service->run();

        return redirect()
            ->route('commands.index')
            ->with('success', __('commands.commands.credits_resync_amount_local.success', [
                'affected' => $result['affected_count'],
                'updated' => $result['updated_count'],
            ]));
    }

    public function syncCredits(): RedirectResponse
    {
        try {
            Artisan::call('sync:credits');

            return redirect()
                ->route('commands.index')
                ->with('success', __('commands.commands.sync_credits.success'));
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('commands.index')
                ->with('error', __('commands.commands.sync_credits.error'));
        }
    }

    public function syncAvshocrecat(): RedirectResponse
    {
        try {
            Artisan::call('sync:avshocrecat');

            return redirect()
                ->route('commands.index')
                ->with('success', __('commands.commands.sync_avshocrecat.success'));
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('commands.index')
                ->with('error', __('commands.commands.sync_avshocrecat.error'));
        }
    }
}
