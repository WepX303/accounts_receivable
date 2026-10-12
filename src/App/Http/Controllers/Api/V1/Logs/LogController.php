<?php

namespace App\Http\Controllers\Api\V1\Logs;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Logs\LogsController;
use App\Support\ApiResponse;
use App\Support\Panel;
use App\Support\ViewData;

class LogController extends Controller
{
    /**
     * The panel's log page: activity, sign-ins and security alerts in one list, with its filters and counters.
     */
    public function __invoke()
    {
        return ApiResponse::success(ViewData::toArray(Panel::call(LogsController::class, '__invoke')->getData()));
    }
}
