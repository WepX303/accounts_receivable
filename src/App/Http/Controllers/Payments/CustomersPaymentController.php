<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Services\Payments\CreatePaymentData;
use App\Services\Payments\Money;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CustomersPaymentController extends Controller
{
    // INDEX
    public function __invoke(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $id = $this->sanitizeId($request->get('id'));
        $ids = $this->sanitizeIds($request->input('ids', []));

        if ($q !== '') {
            $ids = [];
        } elseif (count($ids) > 0) {
            $q = '';
        } elseif ($id !== null) {
            $q = '';
        }

        // In ids mode, if the incoming id is not in ids, drop it to the first element
        if (count($ids) > 0 && $id !== null && ! in_array($id, $ids, true)) {
            $id = $ids[0] ?? null;
        }

        if (count($ids) === 0 && $id !== null) {
            $ids = [$id];
        }

        $shouldFetchList = (count($ids) > 0) || ($q !== '');

        $customers = collect();
        $selected = null;
        $history = collect();
        $monthlyPayments = collect();

        if ($shouldFetchList) {
            // $listQuery = Credit::query()->with('paidUpdatedByUser');

            $listQuery = Credit::query()
                ->where('active', true)
                ->with('paidUpdatedByUser');

            if (count($ids) > 0) {
                $listQuery->whereIn('source_id', $ids);
            }

            if ($q !== '') {
                $like = '%' . $q . '%';
                $listQuery->where(function ($qq) use ($like) {
                    $qq->where('name', 'ilike', $like)
                        ->orWhere('phone', 'ilike', $like)
                        ->orWhere('passport', 'ilike', $like)
                        ->orWhere('contract', 'ilike', $like)
                        ->orWhere('clientref', 'ilike', $like);
                });
            }

            $appends = [];
            if ($q !== '') {
                $appends['q'] = $q;
            }
            if (count($ids) > 0) {
                $appends['ids'] = $ids;
            }
            if ($id !== null) {
                $appends['id'] = $id;
            }

            $customers = $listQuery
                ->orderByDesc('rv_bigint')
                ->paginate(12)
                ->appends($appends);

            $pageItems = collect($customers->items());

            if ($id !== null) {
                $selected = $pageItems->firstWhere('source_id', $id);

                if (! $selected) {
                    $selected = Credit::query()
                        ->where('active', true)
                        ->with('paidUpdatedByUser')
                        ->where('source_id', $id)
                        ->first();
                }
            }

            if (! $selected) {
                $selected = $pageItems->first();
            }
        }

        if ($selected) {
            $history = CreditPayment::query()
                ->with([
                    'createdByUser:id,firstname,lastname',
                    'correctedByUser:id,firstname,lastname',
                    'voidedByUser:id,firstname,lastname',
                ])
                ->where('credit_source_id', (int) $selected->source_id)
                ->orderByDesc('id')
                ->limit(10)
                ->get();

            $monthlyPayments = \App\Models\AvshocrecatReport::query()
                ->where('sertnama_nomeri', (string) $selected->contract)
                ->orderBy('tolejek_senesi')
                ->get();
        }

        return view('pages.payments.index', [
            'customers' => $customers,
            'selected' => $selected,
            'history' => $history,
            'monthlyPayments' => $monthlyPayments,
            'q' => $q,
            'emptyMode' => ! $shouldFetchList,
        ]);
    }

    // STORE
    public function store(Request $request, PaymentService $payments)
    {

        $user = Auth::user();
        if (! $user) {
            return back()->with('warning', __('validations/validations.payments.auth_required'));
        }

        $customerId = $this->sanitizeId($request->input('customer_id'));
        if ($customerId === null) {
            return back()->with('warning', __('validations/validations.payments.customer_not_selected'));
        }

        $data = new CreatePaymentData(
            creditSourceId: $customerId,
            method: (string) $request->input('payment_method', 'cash'),
            received: Money::parse($request->input('pay_amount', '0')),
            cashTotal: Money::parse($request->input('cash_total', '0')),
            cardTotal: Money::parse($request->input('card_total', '0')),
            receiverPhoneNumber: (string) $request->input('receiver_phone_number', ''),
            note: (string) $request->input('note', ''),
        );

        try {
            // Before the date is read, so the first problem reported stays the same.
            $payments->validate($data);

            $paymentAtRaw = (string) $request->input('payment_at', '');
            $paymentAtRaw = trim($paymentAtRaw);

            if ($paymentAtRaw !== '') {
                try {
                    $data = $data->at(Carbon::createFromFormat('Y-m-d\TH:i', $paymentAtRaw));
                } catch (\Throwable $e) {
                    return back()->with('warning', __('validations/validations.payments.invalid_payment_date'))->withInput();
                }
            }

            $payments->create($user, $data);
        } catch (PaymentException $e) {
            $response = back()->with('warning', $e->getMessage());

            return $e->errorCode === 'method_invalid' ? $response : $response->withInput();
        }

        $redirectUrl = route('payments', array_merge($request->query(), ['id' => (string) $customerId]));

        return redirect($redirectUrl)->with('success', __('validations/validations.payments.payment_saved'));
    }

    private function sanitizeId($id): ?int
    {
        $v = trim((string) $id);
        if ($v === '' || ! ctype_digit($v)) {
            return null;
        }

        return (int) $v;
    }

    private function sanitizeIds($ids): array
    {
        if (! is_array($ids)) {
            return [];
        }

        return collect($ids)
            ->map(fn($v) => trim((string) $v))
            ->filter(fn($v) => $v !== '' && ctype_digit($v))
            ->map(fn($v) => (int) $v)
            ->unique()
            ->values()
            ->all();
    }
}
