<?php

namespace App\Http\Controllers\Api\V1\Customers;

use App\Exports\CustomersExport;
use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApiCustomersExportController extends Controller
{
    public function __invoke(Request $request): BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'quick_filter' => ['nullable', 'in:all,paid_today,paid_yesterday,paid_7d,paid_14d,paid_1m,paid_3m,paid_6m,paid_9m,paid_12m,has_debt,no_debt,no_payment,blocked,active,bermejek,paid_mismatch'],
        ]);

        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if (!$user) {
            return ApiResponse::error(
                'Unauthorized.',
                401,
                'AUTH_REQUIRED'
            );
        }

        $audit = app(AuditLogger::class);

        $audit->log(
            action: 'api_customers_exported',
            category: 'export',
            subject: $user,
            oldValues: null,
            newValues: null,
            extra: [
                'q' => $request->get('q'),
                'quick_filter' => $request->get('quick_filter'),
            ],
            message: 'Customers export downloaded via API',
            isSuccess: true,
            severity: 'info',
            isSuspicious: false
        );

        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '1024M');
        @set_time_limit(0);

        return Excel::download(
            new CustomersExport($request),
            'customers.xlsx'
        );
    }
}