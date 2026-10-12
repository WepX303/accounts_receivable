<?php

namespace App\Http\Controllers\Api\V1\Sms;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sms\CustomerSmsController;
use App\Support\ApiResponse;
use App\Support\Panel;
use App\Support\ViewData;
use Illuminate\Http\RedirectResponse;

/**
 * SMS distribution up to the spreadsheet of messages, run by the panel's own
 * controller; nothing is sent through the API. The panel keeps the
 * preview in the session between its steps; the app has no session, so each
 * call here carries everything it needs.
 */
class SmsController extends Controller
{
    /**
     * The customers that can be messaged, with the panel's filters and status column.
     */
    public function customers()
    {
        $data = Panel::call(CustomerSmsController::class, 'index')->getData();

        return ApiResponse::success(ViewData::toArray([
            'customers' => $data['customers'],
            'branches' => $data['branches'],
            'statusType' => $data['statusType'],
        ]));
    }

    /**
     * The messages as they would be sent, one per customer.
     */
    public function preview()
    {
        if ($refusal = $this->buildPreview()) {
            return $refusal;
        }

        return ApiResponse::success([
            'rows' => session('sms_preview_rows', []),
            'message' => session('sms_preview_message'),
            'schedule_type' => session('sms_schedule_type'),
            'scheduled_at' => session('sms_scheduled_at'),
        ]);
    }

    /**
     * The preview as the spreadsheet the panel offers: one row per valid phone number.
     */
    public function exportPreview()
    {
        if ($refusal = $this->buildPreview()) {
            return $refusal;
        }

        // Not through Panel::call: the preview just built has to stay in place.
        $response = app()->call([app(CustomerSmsController::class), 'exportPreview']);

        if ($response instanceof RedirectResponse) {
            return ApiResponse::error(
                Panel::refusal($response)?->getData(true)['message'] ?? __('api.request_failed'),
                422,
                'request_failed',
                ['skipped' => session('sms_export_skipped_rows', [])]
            );
        }

        return $response;
    }

    private function buildPreview()
    {
        return Panel::refusal(Panel::call(CustomerSmsController::class, 'preview'));
    }
}
