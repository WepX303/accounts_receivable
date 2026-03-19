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
                'title' => 'Clear All Caches',
                'description' => 'Clears view, config, route and application caches.',
                'route' => route('clear-all-caches'),
                'method' => 'POST',
                'risk' => 'low',
                'confirm_message' => 'Are you sure you want to clear all caches?',
            ],
            [
                'key' => 'credits_resync_amount_local',
                'title' => 'Credits / Resync Amount Local',
                'description' => 'Syncs credits.amount_local with credits.amount for mismatched records.',
                'route' => route('credits.resync-amount-local'),
                'method' => 'POST',
                'risk' => 'low',
                'confirm_message' => 'Are you sure you want to resync amount_local values?',
            ],

        ];

        return view('pages.admin.settings.commands.index', compact('commands'));
    }

    public function clearAllCaches(): RedirectResponse
    {
        Artisan::call('clear:all');

        return redirect()
            ->route('commands.index')
            ->with('success', 'All caches cleared successfully.');
    }

    public function resyncAmountLocal(CreditsResyncAmountLocalService $service): RedirectResponse
    {
        $result = $service->run();

        return redirect()
            ->route('commands.index')
            ->with('success', 'Resync completed. Affected: ' . $result['affected_count'] . ', Updated: ' . $result['updated_count']);
    }
}
