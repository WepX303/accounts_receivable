<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\CreditPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomersPaymentController extends Controller
{
    public function __invoke(Request $request)
    {
        $q   = trim((string) $request->get('q', ''));
        $id  = $this->sanitizeId($request->get('id'));
        $ids = $this->sanitizeIds($request->input('ids', []));

        /**
         * ✅ MOD SEÇİMİ (karışmayı bitiren kural)
         * - q doluysa: SEARCH MODU => id/ids yok say
         * - ids doluysa: IDS MODU => q yok say
         * - id doluysa: DETAIL MODU => q yok say (isteğe bağlı ama stabil)
         */

        // if ($q !== '') {
        //     $id = null;
        //     $ids = [];
        //     } elseif (count($ids) > 0) {
        //         $q = '';
        //         $id = null; 
        //     } elseif ($id !== null) {
        //         $q = ''; 
        //     }

        if ($q !== '') {
            // ✅ SEARCH modu: liste q ile filtrelenir
            // ✅ ids karışmasın diye temizlenir
            // ✅ AMA id KALIR: arama sonuçlarında satıra tıklayınca seçili değişebilsin
            $ids = [];
        } elseif (count($ids) > 0) {
            // ✅ IDS modu: arama kapansın, çoklu liste sabit kalsın
            $q = '';
            // id burada kalsın (ids içinde seçim için)
        } elseif ($id !== null) {
            // ✅ DETAIL modu: arama kapansın
            $q = '';
        }



        // ✅ ids modunda, gelen id ids içinde değilse ilk elemana düş
        if (count($ids) > 0 && $id !== null && !in_array($id, $ids, true)) {
            $id = $ids[0] ?? null;
        }





        if (count($ids) === 0 && $id !== null) {
            $ids = [$id];
        }

        $shouldFetchList = (count($ids) > 0) || ($q !== '');

        $customers = collect();
        $selected  = null;
        $history   = collect();

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

            // $customers = $listQuery
            //     ->orderByDesc('rv_bigint')
            //     ->paginate(12)
            //     ->appends($request->query());

            // $appends = [];
            // if ($q !== '') $appends['q'] = $q;
            // if (count($ids) > 0) $appends['ids'] = $ids; // ids[] olarak gider
            // if ($id !== null) $appends['id'] = $id;

            // $customers = $listQuery
            //     ->orderByDesc('rv_bigint')
            //     ->paginate(12)
            //     ->appends($appends);

            $appends = [];
            if ($q !== '') $appends['q'] = $q;
            if (count($ids) > 0) $appends['ids'] = $ids; // ids[] olarak gider
            if ($id !== null) $appends['id'] = $id;

            $customers = $listQuery
                ->orderByDesc('rv_bigint')
                ->paginate(12)
                ->appends($appends);



            $pageItems = collect($customers->items());

            if ($id !== null) {
                $selected = $pageItems->firstWhere('logicalref', $id);

                if (!$selected) {
                    $selected = Credit::query()
                        ->with('paidUpdatedByUser')
                        ->where('logicalref', $id)
                        ->first();
                }
            }

            if (!$selected) {
                $selected = $pageItems->first();
            }
        }

        if ($selected) {
            $history = CreditPayment::query()
                ->with('createdByUser')
                ->where('credit_logicalref', (int) $selected->logicalref)
                ->orderByDesc('id')
                ->limit(10)
                ->get();
        }

        return view('pages.payments.index', [
            'customers' => $customers,
            'selected'  => $selected,
            'history'   => $history,
            'q'         => $q,
            'emptyMode' => !$shouldFetchList,
        ]);
    }

    public function store(Request $request)
    {


        // $userId = Auth::id();
        // if (!$userId) {
        //     return back()->with('warning', 'Ödeme kaydetmek için giriş yapmalısınız.');
        // }

        $user = Auth::user();
        if (!$user) {
            return back()->with('warning', 'Ödeme kaydetmek için giriş yapmalısınız.');
        }
        $userId = (int) $user->id;


        $customerId = $this->sanitizeId($request->input('customer_id'));
        if ($customerId === null) {
            return back()->with('warning', 'Customer seçilmedi.');
        }

        $method = (string) $request->input('payment_method', 'cash');
        if (!in_array($method, ['cash', 'card', 'mixed', 'phone'], true)) {
            return back()->with('warning', 'Payment method geçersiz.');
        }

        // ✅ Pay Amount = müşterinin verdiği para (RECEIVED)
        $received = $this->toMoney($request->input('pay_amount', '0'));
        if ($received <= 0) {
            return back()->with('warning', 'Pay Amount 0 olamaz.')->withInput();
        }

        $note = trim((string) $request->input('note', ''));
        $note = preg_replace('/\s+/', ' ', $note);

        $cashTotal = $this->toMoney($request->input('cash_total', '0'));
        $cardTotal = $this->toMoney($request->input('card_total', '0'));

        // ✅ cash/card/mixed toplamları "received" ile eşleşmeli
        if ($method === 'cash') {
            $cashTotal = $received;
            $cardTotal = 0.0;
        } elseif ($method === 'card') {
            $cashTotal = 0.0;
            $cardTotal = $received;
        } elseif ($method === 'phone') {
            $cashTotal = 0.0;
            $cardTotal = $received;
        } else {
            if ($cashTotal < 0 || $cardTotal < 0) {
                return back()->with('warning', 'Mixed: Cash/Card negatif olamaz.')->withInput();
            }
            if (abs(($cashTotal + $cardTotal) - $received) > 0.01) {
                return back()->with('warning', 'Mixed: Cash + Card toplamı Pay Amount ile eşit olmalı.')->withInput();
            }
        }

        $now = now();

        try {

            DB::transaction(function () use ($customerId, $received, $userId, $user, $now, $method, $cashTotal, $cardTotal, $note) {

                /** @var \App\Models\Credit $c */
                $c = Credit::query()
                    ->where('logicalref', $customerId)
                    ->lockForUpdate()
                    ->firstOrFail();

                // ✅ SADECE LOCAL
                if ($c->amount_local === null || $c->paid_local === null) {
                    throw new \RuntimeException('Local borç verisi eksik (amount_local / paid_local NULL). Ödeme alınamaz.');
                }

                $totalLocal = (float) $c->amount_local; // TOTAL BORÇ
                $paidLocal  = (float) $c->paid_local;  // TOPLAM ÖDENEN

                // ✅ kapanmışsa ödeme alma (eşik dahil)
                if ($paidLocal >= $totalLocal - 0.01) {
                    throw new \RuntimeException('Bu müşterinin borcu kapanmış (paid_local >= amount_local). Ödeme alınamaz.');
                }

                $remaining = $totalLocal - $paidLocal;
                if ($remaining < 0) $remaining = 0;

                if ($remaining <= 0.01) {
                    throw new \RuntimeException('Bu müşterinin borcu kapanmış (kalan 0). Ödeme alınamaz.');
                }

                // ✅ Para üstü limiti (overpayment guard)
                $maxExtra = 100; // izin verilen max para üstü
                if ($received > $remaining + $maxExtra) {
                    throw new \RuntimeException(
                        'Fazla ödeme çok yüksek. Maksimum para üstü: ' . number_format($maxExtra, 2)
                    );
                }

                // ✅ Para üstü: borca uygulanacak miktar remaining kadar
                $apply  = min($received, $remaining);
                $change = $received - $apply;

                // mixed için transaction içi garanti
                if ($method === 'mixed') {
                    if (abs(($cashTotal + $cardTotal) - $received) > 0.01) {
                        throw new \RuntimeException('Mixed: Cash + Card toplamı Pay Amount ile eşit olmalı.');
                    }
                }

                $oldRemaining = $remaining;
                $oldPaidLocal = $paidLocal;

                $newPaidLocal = $paidLocal + $apply;

                $newRemaining = $totalLocal - $newPaidLocal;
                if ($newRemaining < 0) $newRemaining = 0;

                // audit note
                $detail = [
                    'method=' . $method,

                    'received=' . $this->fmtMoney($received),
                    'applied=' . $this->fmtMoney($apply),
                    'change=' . $this->fmtMoney($change),

                    'cash=' . $this->fmtMoney($cashTotal),
                    'card=' . $this->fmtMoney($cardTotal),

                    'total_local=' . $this->fmtMoney($totalLocal),

                    'old_paid_local=' . $this->fmtMoney($oldPaidLocal),
                    'new_paid_local=' . $this->fmtMoney($newPaidLocal),

                    'old_remaining=' . $this->fmtMoney($oldRemaining),
                    'new_remaining=' . $this->fmtMoney($newRemaining),
                ];

                $finalNote = implode(' | ', $detail);
                if ($note !== '') $finalNote .= ' | note=' . $note;

                // ✅ sadece paid_local artar
                $c->forceFill([
                    'paid_local'      => $this->fmtMoney($newPaidLocal),
                    'paid_updated_by' => $userId,
                    'paid_updated_at' => $now,
                    'paid_note'       => $finalNote,
                ])->save();

                // history:
                // pay_amount = received, change_amount = change
                // old_amount_local/new_amount_local = remaining (log)
                CreditPayment::create([
                    'credit_logicalref' => (int) $c->logicalref,

                    // ✅ SNAPSHOT (kime ait olduğu ödeme kaydından anlaşılsın)
                    'customer_name'     => mb_substr((string) $c->name, 0, 255),
                    'customer_phone'    => mb_substr((string) $c->phone, 0, 50),
                    'customer_passport' => mb_substr((string) $c->passport, 0, 50),
                    'customer_contract' => mb_substr((string) $c->contract, 0, 50),
                    'branch'            => mb_substr((string) $c->branch, 0, 50),


                    // ✅ user snapshot
                    'created_by'        => $userId,
                    'created_by_name'   => mb_substr((string) $user->full_name, 0, 255),
                    'created_by_email'  => mb_substr((string) $user->email, 0, 255),
                    'created_by_phone'  => mb_substr((string) $user->phonenumber, 0, 50),


                    'pay_amount'        => $this->fmtMoney($received),
                    'change_amount'     => $this->fmtMoney($change),

                    'method'            => $method,
                    'cash_amount'       => $this->fmtMoney($cashTotal),
                    'card_amount'       => $this->fmtMoney($cardTotal),

                    'old_amount_local'  => $this->fmtMoney($oldRemaining),
                    'new_amount_local'  => $this->fmtMoney($newRemaining),

                    'old_paid_local'    => $this->fmtMoney($oldPaidLocal),
                    'new_paid_local'    => $this->fmtMoney($newPaidLocal),

                    'note'              => $note !== '' ? $note : null,
                    'created_at'        => $now,
                ]);
            });
        } catch (\Throwable $e) {
            $msg = $e instanceof \RuntimeException ? $e->getMessage() : 'Payment kaydedilemedi.';
            return back()->with('warning', $msg)->withInput();
        }

        $redirectUrl = route('payments', array_merge($request->query(), ['id' => (string) $customerId]));
        return redirect($redirectUrl)->with('success', 'Payment saved successfully.');
    }


    private function sanitizeId($id): ?int
    {
        $v = trim((string) $id);
        if ($v === '' || !ctype_digit($v)) return null;
        return (int) $v;
    }

    private function sanitizeIds($ids): array
    {
        if (!is_array($ids)) return [];
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
        if ($s === '') return 0.0;

        $s = str_replace([' ', "\u{00A0}"], '', $s);

        $hasDot   = str_contains($s, '.');
        $hasComma = str_contains($s, ',');

        if ($hasDot && $hasComma) {
            $lastDot   = strrpos($s, '.');
            $lastComma = strrpos($s, ',');

            if ($lastComma > $lastDot) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);
            }
        } else {
            if ($hasComma && !$hasDot) {
                $s = str_replace(',', '.', $s);
            }
        }

        $n = (float) $s;
        if (!is_finite($n)) return 0.0;

        return round($n, 2);
    }

    private function fmtMoney(float $n): string
    {
        return number_format(round($n, 2), 2, '.', '');
    }
}
