<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports;
use App\Support\ApiResponse;
use App\Support\Panel;
use App\Support\ViewData;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

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
        'avshocrecat' => [Reports\AvshocrecatReportController::class, 'index', 'reports.avshocrecat'],
        'payment-calendar' => [Reports\PaymentCalendarReportController::class, '__invoke', 'reports'],
        'payment-calendar/details' => [Reports\PaymentCalendarReportDetailController::class, '__invoke', 'reports'],
        'overdue-payments' => [Reports\OverduePaymentsReportController::class, '__invoke', 'reports'],
        'collection-performance' => [Reports\CollectionPerformanceReportController::class, '__invoke', 'reports'],
        'collection-performance/details' => [Reports\CollectionPerformanceDetailsController::class, '__invoke', 'reports'],
        'daily-cash-closing' => [Reports\DailyCashClosingReportController::class, '__invoke', 'reports'],
        'collection-trend' => [Reports\CollectionTrendReportController::class, '__invoke', 'reports'],
        'promise-to-pay' => [Reports\PromiseToPayReportController::class, '__invoke', 'reports'],
        'recovery-effectiveness' => [Reports\RecoveryEffectivenessReportController::class, '__invoke', 'reports'],
        'customer-statement' => [Reports\CustomerStatementReportController::class, '__invoke', 'reports'],
        'void-correction' => [Reports\VoidCorrectionReportController::class, '__invoke', 'reports'],
        'balance-integrity' => [Reports\BalanceIntegrityReportController::class, '__invoke', 'reports'],
    ];

    /**
     * Address under /reports => the panel action that builds the spreadsheet.
     */
    public const EXPORTS = [
        'avshocrecat/export' => [Reports\AvshocrecatReportController::class, 'export', 'reports.avshocrecat'],
        'payment-calendar/export' => [Reports\PaymentCalendarReportController::class, 'export', 'reports'],
        'payment-calendar/details/export' => [Reports\PaymentCalendarDetailsExportController::class, '__invoke', 'reports'],
        'overdue-payments/export' => [Reports\OverduePaymentsReportExportController::class, '__invoke', 'reports'],
        'collection-performance/export' => [Reports\CollectionPerformanceExportController::class, '__invoke', 'reports'],
        'collection-performance/details/export' => [Reports\CollectionPerformanceDetailsExportController::class, '__invoke', 'reports'],
        'daily-cash-closing/export' => [Reports\DailyCashClosingExportController::class, '__invoke', 'reports'],
        'promise-to-pay/export' => [Reports\PromiseToPayExportController::class, '__invoke', 'reports'],
        'recovery-effectiveness/export' => [Reports\RecoveryEffectivenessExportController::class, '__invoke', 'reports'],
        'customer-statement/export' => [Reports\CustomerStatementReportController::class, 'export', 'reports'],
        'void-correction/export' => [Reports\VoidCorrectionExportController::class, '__invoke', 'reports'],
        'balance-integrity/export' => [Reports\BalanceIntegrityExportController::class, '__invoke', 'reports'],
    ];

    public function show(string $report)
    {
        [$controller, $method] = self::REPORTS[$report];

        $response = Panel::call($controller, $method);

        if ($response instanceof View) {
            return ApiResponse::success(ViewData::toArray($response->getData()));
        }

        return $this->refused($response);
    }

    public function export(string $report)
    {
        [$controller, $method] = self::EXPORTS[$report];

        $response = Panel::call($controller, $method);

        return $response instanceof RedirectResponse ? $this->refused($response) : $response;
    }

    private function refused(mixed $response)
    {
        return ($response instanceof RedirectResponse ? Panel::refusal($response) : null)
            ?? ApiResponse::error(__('api.request_failed'), 422, 'request_failed');
    }
}
