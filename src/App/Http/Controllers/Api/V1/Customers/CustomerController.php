<?php

namespace App\Http\Controllers\Api\V1\Customers;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreditResource;
use App\Http\Resources\CustomerRowResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\ScheduleRowResource;
use App\Models\AvshocrecatReport;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Services\Customers\CustomerListService;
use App\Services\Payments\Money;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    private const PAYMENT_RELATIONS = [
        'createdByUser:id,firstname,lastname',
        'correctedByUser:id,firstname,lastname',
        'voidedByUser:id,firstname,lastname',
    ];

    /**
     * The cashier's search: active credits, newest first.
     */
    public function search(Request $request)
    {
        $data = $request->validate([
            'q' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $q = trim((string) ($data['q'] ?? ''));

        $credits = Credit::query()
            ->where('active', true)
            ->when($q !== '', fn ($query) => $query->matching($q))
            ->orderByDesc('rv_bigint')
            ->paginate((int) ($data['per_page'] ?? 20));

        return ApiResponse::paginated($credits, CreditResource::collection($credits->getCollection())->resolve());
    }

    /**
     * Everything the payment screen shows for one customer: the debt, the
     * instalment plan and the latest payments.
     */
    public function show(Credit $credit)
    {
        abort_unless($credit->active, 404);

        $credit->load('paidUpdatedByUser:id,firstname,lastname');

        $payments = CreditPayment::query()
            ->with(self::PAYMENT_RELATIONS)
            ->where('credit_source_id', (int) $credit->source_id)
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $schedule = AvshocrecatReport::query()
            ->where('sertnama_nomeri', (string) $credit->contract)
            ->orderBy('tolejek_senesi')
            ->get();

        return ApiResponse::success([
            'credit' => [
                ...(new CreditResource($credit))->resolve(),
                'logical_ref' => $credit->logicalref,
                'is_active' => (bool) $credit->active,
                // What the source system holds; the panel shows it next to the local figures.
                'source_amount' => $credit->amount,
                'source_paid' => $credit->paid,
                'note' => $credit->note,
                'paid_note' => $credit->paid_note,
                'promised_payment_date' => $credit->willpaiddate?->toDateString(),
                'last_noted_at' => $credit->lastnoteddate?->toIso8601String(),
                'last_payment_at' => $credit->paid_updated_at?->toIso8601String(),
                'last_payment_by' => $credit->paidUpdatedByUser?->full_name,
            ],
            'schedule' => ScheduleRowResource::collection($schedule)->resolve(),
            'recent_payments' => PaymentResource::collection($payments)->resolve(),
        ]);
    }

    /**
     * The management list: the panel's filters, period column and counters.
     */
    public function index(Request $request, CustomerListService $customers)
    {
        $filters = $request->validate([
            ...CustomerListService::rules(),
            'per_page' => 'nullable|integer|min:1|max:50',
        ], CustomerListService::messages());

        $data = $customers->list($filters, (int) ($filters['per_page'] ?? 25));
        $stats = $data['stats'];

        return ApiResponse::paginated(
            $data['credit_users'],
            CustomerRowResource::collection($data['credit_users']->getCollection())->resolve(),
            ['summary' => [
                'quick_filter' => $data['quick'],
                'period_label' => $data['periodLabel'],
                'paid_today_sum' => Money::format($stats['paid_today_sum']),
                'paid_today_count' => $stats['paid_today_cnt'],
                'paid_yesterday_sum' => Money::format($stats['paid_y_sum']),
                'paid_yesterday_count' => $stats['paid_y_cnt'],
                'has_debt_count' => $stats['has_debt_cnt'],
                'paid_mismatch_count' => $stats['paid_mismatch_cnt'],
            ]]
        );
    }

    /**
     * The payment statement of one credit: every payment, voided ones included and marked.
     */
    public function statement(Credit $credit)
    {
        abort_unless($credit->active, 404);

        $payments = CreditPayment::query()
            ->with(self::PAYMENT_RELATIONS)
            ->where('credit_source_id', (int) $credit->source_id)
            ->orderByDesc('id')
            ->get();

        return ApiResponse::success([
            'credit' => (new CreditResource($credit))->resolve(),
            'payments' => PaymentResource::collection($payments)->resolve(),
        ]);
    }

    public function payments(Request $request, Credit $credit)
    {
        abort_unless($credit->active, 404);

        $data = $request->validate([
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $payments = CreditPayment::query()
            ->with(self::PAYMENT_RELATIONS)
            ->where('credit_source_id', (int) $credit->source_id)
            ->orderByDesc('id')
            ->paginate((int) ($data['per_page'] ?? 20));

        return ApiResponse::paginated($payments, PaymentResource::collection($payments->getCollection())->resolve());
    }
}
