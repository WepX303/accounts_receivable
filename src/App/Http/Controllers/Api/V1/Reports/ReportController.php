<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports as Panel;
use App\Support\ApiResponse;
use App\Support\ViewData;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * The reports are the panel's own: each one is run by the controller that
 * serves the page, and what it hands to its view is returned as JSON. A change
 * to a report in the panel reaches the app with nothing to keep in step.
 */
class ReportController extends Controller
{
    /**
     * Address under /reports => the panel action and the ability that guards it.
     */
    public const REPORTS = [
        'avshocrecat' => [Panel\AvshocrecatReportController::class, 'index', 'reports.avshocrecat'],
        'payment-calendar' => [Panel\PaymentCalendarReportController::class, '__invoke', 'reports'],
        'payment-calendar/details' => [Panel\PaymentCalendarReportDetailController::class, '__invoke', 'reports'],
        'overdue-payments' => [Panel\OverduePaymentsReportController::class, '__invoke', 'reports'],
        'collection-performance' => [Panel\CollectionPerformanceReportController::class, '__invoke', 'reports'],
        'collection-performance/details' => [Panel\CollectionPerformanceDetailsController::class, '__invoke', 'reports'],
        'daily-cash-closing' => [Panel\DailyCashClosingReportController::class, '__invoke', 'reports'],
        'collection-trend' => [Panel\CollectionTrendReportController::class, '__invoke', 'reports'],
        'promise-to-pay' => [Panel\PromiseToPayReportController::class, '__invoke', 'reports'],
        'recovery-effectiveness' => [Panel\RecoveryEffectivenessReportController::class, '__invoke', 'reports'],
        'customer-statement' => [Panel\CustomerStatementReportController::class, '__invoke', 'reports'],
        'void-correction' => [Panel\VoidCorrectionReportController::class, '__invoke', 'reports'],
        'balance-integrity' => [Panel\BalanceIntegrityReportController::class, '__invoke', 'reports'],
    ];

    /**
     * Address under /reports => the panel action that builds the spreadsheet.
     */
    public const EXPORTS = [
        'avshocrecat/export' => [Panel\AvshocrecatReportController::class, 'export', 'reports.avshocrecat'],
        'payment-calendar/export' => [Panel\PaymentCalendarReportController::class, 'export', 'reports'],
        'payment-calendar/details/export' => [Panel\PaymentCalendarDetailsExportController::class, '__invoke', 'reports'],
        'overdue-payments/export' => [Panel\OverduePaymentsReportExportController::class, '__invoke', 'reports'],
        'collection-performance/export' => [Panel\CollectionPerformanceExportController::class, '__invoke', 'reports'],
        'collection-performance/details/export' => [Panel\CollectionPerformanceDetailsExportController::class, '__invoke', 'reports'],
        'daily-cash-closing/export' => [Panel\DailyCashClosingExportController::class, '__invoke', 'reports'],
        'promise-to-pay/export' => [Panel\PromiseToPayExportController::class, '__invoke', 'reports'],
        'recovery-effectiveness/export' => [Panel\RecoveryEffectivenessExportController::class, '__invoke', 'reports'],
        'customer-statement/export' => [Panel\CustomerStatementReportController::class, 'export', 'reports'],
        'void-correction/export' => [Panel\VoidCorrectionExportController::class, '__invoke', 'reports'],
        'balance-integrity/export' => [Panel\BalanceIntegrityExportController::class, '__invoke', 'reports'],
    ];

    public function show(string $report)
    {
        $response = $this->run(self::REPORTS[$report]);

        if ($response instanceof View) {
            return ApiResponse::success(ViewData::toArray($response->getData()));
        }

        return $this->refused($response);
    }

    public function export(string $report)
    {
        $response = $this->run(self::EXPORTS[$report]);

        return $response instanceof RedirectResponse ? $this->refused($response) : $response;
    }

    private function run(array $target): mixed
    {
        [$controller, $method] = $target;

        return app()->call([app($controller), $method]);
    }

    /**
     * The panel answers a request it cannot serve by going back with a message;
     * here the message itself is the answer.
     */
    private function refused(mixed $response)
    {
        $message = null;

        if ($response instanceof RedirectResponse) {
            $session = $response->getSession();

            foreach (['export_error', 'error', 'warning'] as $key) {
                $message ??= $session?->get($key);
            }
        }

        return ApiResponse::error($message ?: __('api.request_failed'), Response::HTTP_UNPROCESSABLE_ENTITY, 'request_failed');
    }
}
