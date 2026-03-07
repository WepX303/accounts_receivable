<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\CreditPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        if ($shouldFetchList) {
            $listQuery = Credit::query()->with('paidUpdatedByUser');

            if (count($ids) > 0) {
                $listQuery->whereIn('logicalref', $ids);
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
                $selected = $pageItems->firstWhere('logicalref', $id);

                if (! $selected) {
                    $selected = Credit::query()
                        ->with('paidUpdatedByUser')
                        ->where('logicalref', $id)
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
                ->where('credit_logicalref', (int) $selected->logicalref)
                ->orderByDesc('id')
                ->limit(10)
                ->get();
        }

        return view('pages.payments.index', [
            'customers' => $customers,
            'selected' => $selected,
            'history' => $history,
            'q' => $q,
            'emptyMode' => ! $shouldFetchList,
        ]);
    }

    // STORE
    public function store(Request $request)
    {

        $user = Auth::user();
        if (! $user) {
            return back()->with('warning', __('validations/validations.payments.auth_required'));
        }
        $userId = (int) $user->id;

        $customerId = $this->sanitizeId($request->input('customer_id'));
        if ($customerId === null) {
            return back()->with('warning', __('validations/validations.payments.customer_not_selected'));
        }

        $method = (string) $request->input('payment_method', 'cash');
        if (! in_array($method, ['cash', 'card', 'mixed', 'phone'], true)) {
            return back()->with('warning', __('validations/validations.payments.method_invalid'));
        }

        // Payment Amount = the money given by the customer (RECEIVED)
        $received = $this->toMoney($request->input('pay_amount', '0'));
        if ($received <= 0) {
            return back()->with('warning', __('validations/validations.payments.pay_amount_zero'))->withInput();
        }

        $note = trim((string) $request->input('note', ''));
        $note = preg_replace('/\s+/', ' ', $note);

        // $cashTotal = $this->toMoney($request->input('cash_total', '0'));
        // $cardTotal = $this->toMoney($request->input('card_total', '0'));

        // // Cash/card/mixed totals must match ‘received’
        // if ($method === 'cash') {
        //     $cashTotal = $received;
        //     $cardTotal = 0.0;
        // } elseif ($method === 'card') {
        //     $cashTotal = 0.0;
        //     $cardTotal = $received;
        // } elseif ($method === 'phone') {
        //     $cashTotal = 0.0;
        //     $cardTotal = $received;
        // } else {
        //     if ($cashTotal < 0 || $cardTotal < 0) {
        //         return back()->with('warning', __('validations/validations.payments.mixed_negative'))->withInput();
        //     }
        //     if (abs(($cashTotal + $cardTotal) - $received) > 0.01) {
        //         return back()->with('warning', __('validations/validations.payments.mixed_sum_must_equal'))->withInput();
        //     }
        // }

        $cashTotal = $this->toMoney($request->input('cash_total', '0'));
        $cardTotal = $this->toMoney($request->input('card_total', '0'));
        $phoneTotal = 0.0;

        // Cash/card/mixed/phone totals must match received
        if ($method === 'cash') {
            $cashTotal = $received;
            $cardTotal = 0.0;
            $phoneTotal = 0.0;
        } elseif ($method === 'card') {
            $cashTotal = 0.0;
            $cardTotal = $received;
            $phoneTotal = 0.0;
        } elseif ($method === 'phone') {
            $cashTotal = 0.0;
            $cardTotal = 0.0;
            $phoneTotal = $received;
        } else {
            $phoneTotal = 0.0;

            if ($cashTotal < 0 || $cardTotal < 0) {
                return back()->with('warning', __('validations/validations.payments.mixed_negative'))->withInput();
            }

            if (abs(($cashTotal + $cardTotal) - $received) > 0.01) {
                return back()->with('warning', __('validations/validations.payments.mixed_sum_must_equal'))->withInput();
            }
        }

        $paymentAtRaw = (string) $request->input('payment_at', '');
        $paymentAtRaw = trim($paymentAtRaw);

        $enteredAt = now();
        $paymentAtProvided = ($paymentAtRaw !== '');

        if ($paymentAtProvided) {
            try {
                $now = Carbon::createFromFormat('Y-m-d\TH:i', $paymentAtRaw);
            } catch (\Throwable $e) {
                return back()->with('warning', __('validations/validations.payments.invalid_payment_date'))->withInput();
            }
        } else {
            $now = $enteredAt;
        }
        // Future date not allowed
        if ($now->isFuture()) {
            return back()->with('warning', __('validations/validations.payments.future_payment_date_not_allowed'))->withInput();
        }

        $backdated = $paymentAtProvided && $now->lt($enteredAt->copy()->startOfMinute());
        try {

            // DB::transaction(function () use ($customerId, $received, $userId, $user, $now, $enteredAt, $paymentAtProvided, $backdated, $method, $cashTotal, $cardTotal,  $note) {
            DB::transaction(function () use ($customerId, $received, $userId, $user, $now, $enteredAt, $paymentAtProvided, $backdated, $method, $cashTotal, $cardTotal, $phoneTotal, $note) {
                /** @var \App\Models\Credit $c */
                $c = Credit::query()
                    ->where('logicalref', $customerId)
                    ->lockForUpdate()
                    ->firstOrFail();

                // ONLY LOCAL
                if ($c->amount_local === null || $c->paid_local === null) {
                    throw new \RuntimeException(__('validations/validations.payments.local_debt_missing'));
                }

                $totalLocal = (float) $c->amount_local; // TOTAL DEBT
                $paidLocal = (float) $c->paid_local;  // TOTAL PAID

                // If closed, receive payment (including threshold)
                if ($paidLocal >= $totalLocal - 0.01) {
                    throw new \RuntimeException(__('validations/validations.payments.debt_closed_paid_ge_total'));
                }

                $remaining = $totalLocal - $paidLocal;
                if ($remaining < 0) {
                    $remaining = 0;
                }

                if ($remaining <= 0.01) {
                    throw new \RuntimeException(__('validations/validations.payments.debt_closed_remaining_zero'));
                }

                // Overpayment limit (overpayment guard)
                $maxExtra = 100;  // maximum permitted change
                if ($received > $remaining + $maxExtra) {
                    throw new \RuntimeException(
                        __('validations/validations.payments.overpayment_too_high') . number_format($maxExtra, 2)
                    );
                }

                // Change: the amount to be applied to the debt remaining
                $apply = min($received, $remaining);
                $change = $received - $apply;

                // Mixed transaction-internal guarantee
                if ($method === 'mixed') {
                    if (abs(($cashTotal + $cardTotal) - $received) > 0.01) {
                        throw new \RuntimeException(__('validations/validations.payments.mixed_sum_must_equal_tx'));
                    }
                }

                $oldRemaining = $remaining;
                $oldPaidLocal = $paidLocal;

                $newPaidLocal = $paidLocal + $apply;

                $newRemaining = $totalLocal - $newPaidLocal;
                if ($newRemaining < 0) {
                    $newRemaining = 0;
                }

                // Audit note
                $detail = [

                    'backdated=' . ($backdated ? 'yes' : 'no'),
                    'payment_at=' . $now->format('Y-m-d H:i:s'),
                    'entered_at=' . $enteredAt->format('Y-m-d H:i:s'),

                    'method=' . $method,

                    'received=' . $this->fmtMoney($received),
                    'applied=' . $this->fmtMoney($apply),
                    'change=' . $this->fmtMoney($change),

                    'cash=' . $this->fmtMoney($cashTotal),
                    'card=' . $this->fmtMoney($cardTotal),
                    'phone=' . $this->fmtMoney($phoneTotal),

                    'total_local=' . $this->fmtMoney($totalLocal),

                    'old_paid_local=' . $this->fmtMoney($oldPaidLocal),
                    'new_paid_local=' . $this->fmtMoney($newPaidLocal),

                    'old_remaining=' . $this->fmtMoney($oldRemaining),
                    'new_remaining=' . $this->fmtMoney($newRemaining),
                ];

                $finalNote = implode(' | ', $detail);
                if ($note !== '') {
                    $finalNote .= ' | note=' . $note;
                }

                // Only paid_local increases
                $c->forceFill([
                    'paid_local' => $this->fmtMoney($newPaidLocal),
                    'paid_updated_by' => $userId,
                    'paid_updated_at' => $now,
                    'paid_note' => $finalNote,
                ])->save();

                // History:
                // pay_amount = received, change_amount = change
                // old_amount_local/new_amount_local = remaining (log)
                CreditPayment::create([
                    'credit_logicalref' => (int) $c->logicalref,

                    // SNAPSHOT (the owner should be clear from the payment record)
                    'customer_name' => mb_substr((string) $c->name, 0, 255),
                    'customer_phone' => mb_substr((string) $c->phone, 0, 50),
                    'customer_passport' => mb_substr((string) $c->passport, 0, 50),
                    'customer_contract' => mb_substr((string) $c->contract, 0, 50),
                    'branch' => mb_substr((string) $c->branch, 0, 50),

                    // User snapshot
                    'created_by' => $userId,
                    'created_by_name' => mb_substr((string) $user->full_name, 0, 255),
                    'created_by_email' => mb_substr((string) $user->email, 0, 255),
                    'created_by_phone' => mb_substr((string) $user->phonenumber, 0, 50),

                    'pay_amount' => $this->fmtMoney($received),
                    'change_amount' => $this->fmtMoney($change),

                    'method' => $method,
                    'cash_amount' => $this->fmtMoney($cashTotal),
                    'card_amount' => $this->fmtMoney($cardTotal),
                    'phone_amount' => $this->fmtMoney($phoneTotal),

                    'old_amount_local' => $this->fmtMoney($oldRemaining),
                    'new_amount_local' => $this->fmtMoney($newRemaining),

                    'old_paid_local' => $this->fmtMoney($oldPaidLocal),
                    'new_paid_local' => $this->fmtMoney($newPaidLocal),

                    'note' => $note !== '' ? $note : null,
                    'created_at' => $now,
                ]);
            });
        } catch (\Throwable $e) {
            $msg = $e instanceof \RuntimeException ? $e->getMessage() : __('validations/validations.payments.payment_save_failed');

            return back()->with('warning', $msg)->withInput();
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

    private function toMoney($v): float
    {
        $s = trim((string) $v);
        if ($s === '') {
            return 0.0;
        }

        $s = str_replace([' ', "\u{00A0}"], '', $s);

        $hasDot = str_contains($s, '.');
        $hasComma = str_contains($s, ',');

        if ($hasDot && $hasComma) {
            $lastDot = strrpos($s, '.');
            $lastComma = strrpos($s, ',');

            if ($lastComma > $lastDot) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);
            }
        } else {
            if ($hasComma && ! $hasDot) {
                $s = str_replace(',', '.', $s);
            }
        }

        $n = (float) $s;
        if (! is_finite($n)) {
            return 0.0;
        }

        return round($n, 2);
    }

    private function fmtMoney(float $n): string
    {
        return number_format(round($n, 2), 2, '.', '');
    }
}
