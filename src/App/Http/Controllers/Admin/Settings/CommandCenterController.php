<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Services\Credits\CreditsResyncAmountLocalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class CommandCenterController extends Controller
{
    public function index(): View
    {
        $commands = [
            [
                'key' => 'clear_all_caches',
                // 'title' => 'Clear All Caches',
                // 'description' => 'Clears view, config, route and application caches.',
                'title' => __('commands.commands.clear_all_caches.title'),
                'description' => __('commands.commands.clear_all_caches.description'),
                'route' => route('clear-all-caches'),
                'method' => 'POST',
                'risk' => 'low',
                // 'confirm_message' => 'Are you sure you want to clear all caches?',
                'confirm_message' => __('commands.commands.clear_all_caches.confirm'),
            ],
            [
                'key' => 'credits_resync_amount_local',
                // 'title' => 'Credits / Resync Amount Local',
                'title' => __('commands.commands.credits_resync_amount_local.title'),
                // 'description' => 'Syncs credits.amount_local with credits.amount for mismatched records.',
                'description' => __('commands.commands.credits_resync_amount_local.description'),
                'route' => route('credits.resync-amount-local'),
                'method' => 'POST',
                'risk' => 'low',
                // 'confirm_message' => 'Are you sure you want to resync amount_local values?',
                'confirm_message' => __('commands.commands.credits_resync_amount_local.confirm'),
            ],

        ];

        return view('pages.admin.settings.commands.index', compact('commands'));
    }

    public function clearAllCaches(): RedirectResponse
    {
        Artisan::call('clear:all');

        return redirect()
            ->route('commands.index')
            // ->with('success', 'All caches cleared successfully.');
            ->with('success', __('commands.commands.clear_all_caches.success'));
    }

    public function resyncAmountLocal(CreditsResyncAmountLocalService $service): RedirectResponse
    {
        $result = $service->run();

        return redirect()
            ->route('commands.index')
            // ->with('success', 'Resync completed. Affected: ' . $result['affected_count'] . ', Updated: ' . $result['updated_count']);
            ->with('success', __('commands.commands.credits_resync_amount_local.success', [
                'affected' => $result['affected_count'],
                'updated' => $result['updated_count'],
            ]));
    }
}
