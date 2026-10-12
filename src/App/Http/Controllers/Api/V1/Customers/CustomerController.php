<?php

namespace App\Http\Controllers\Api\V1\Customers;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreditResource;
use App\Http\Resources\PaymentResource;
use App\Models\AvshocrecatReport;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    private const PAYMENT_RELATIONS = [
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
                'note' => $credit->note,
                'promised_payment_date' => $credit->willpaiddate?->toDateString(),
                'last_noted_at' => $credit->lastnoteddate?->toIso8601String(),
                'last_payment_at' => $credit->paid_updated_at?->toIso8601String(),
                'last_payment_by' => $credit->paidUpdatedByUser?->full_name,
            ],
            'schedule' => $schedule->map(fn (AvshocrecatReport $row) => [
                'due_date' => $row->tolejek_senesi,
                'debit' => $row->kt_cykdajy,
                'credit' => $row->dt_girdeji,
                'monthly_payment' => $row->aylyk_tolegi,
                'remaining' => $row->galyndy,
                'status' => $row->statusy,
            ])->all(),
            'recent_payments' => PaymentResource::collection($payments)->resolve(),
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
