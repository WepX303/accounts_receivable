<?php

namespace App\Services\Contracts;

use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\CustomerContract;
use App\Models\Scopes\BranchScope;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\Access;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Makes, changes, approves, cancels and prints şertnamas.
 *
 * Contracts stand apart from the money: credits and payments are only read
 * here, to fill the form in, and are never written to.
 */
class ContractService
{
    public const PREFIX = 'UT';

    private const MARK_DRAFT = 'GARALAMA';

    private const MARK_CANCELLED = 'ÝATYRYLDY';

    public function __construct(
        private AuditLogger $audit,
        private ContractPdfService $pdf,
    ) {
    }

    /**
     * "I-AŞ 350924" → ["I-AŞ", "350924"]: the number is the digits at the end,
     * the series whatever comes before them.
     *
     * @return array{0: ?string, 1: ?string}
     */
    public static function splitPassport(?string $passport): array
    {
        $passport = trim(preg_replace('/\s+/u', ' ', (string) $passport));

        if ($passport === '') {
            return [null, null];
        }

        if (preg_match('/^(.*?)[\s№]*(\d+)$/u', $passport, $m)) {
            $series = trim($m[1], " -№");

            return [$series !== '' ? $series : null, $m[2]];
        }

        return [$passport, null];
    }

    /**
     * The form, filled from a credit the user can see.
     *
     * @return array<string, mixed>
     */
    public function prefill(Credit $credit): array
    {
        [$series, $number] = self::splitPassport($credit->passport);

        return [
            'credit_source_id' => (int) $credit->source_id,
            'branch' => $credit->branch,
            'customer_name' => trim((string) $credit->name),
            'passport_series' => $series,
            'passport_number' => $number,
            // The source keeps the home address here; the cashier checks it.
            'address' => trim((string) $credit->assurance) ?: null,
            'phone' => trim((string) $credit->phone) ?: null,
            'history' => $this->history($series, $number),
        ];
    }

    /**
     * Every credit the buyer has had, as page 2 lists them.
     *
     * Deliberately read across all branches (the business asked for the
     * buyer's whole record), and only for this list: the contract itself and
     * everything else stays within the user's branches.
     *
     * @return list<array<string, string>>
     */
    public function history(?string $series, ?string $number): array
    {
        if ($number === null || $number === '') {
            return [];
        }

        $wanted = $this->passportKey($series . $number);

        $credits = Credit::withoutGlobalScope(BranchScope::class)
            ->where('active', true)
            ->where('passport', 'like', '%' . $number . '%')
            ->orderBy('date_')
            ->orderBy('source_id')
            ->get()
            // The digits alone are not enough: the series has to match too.
            ->filter(fn (Credit $c) => $this->passportKey((string) $c->passport) === $wanted)
            ->values();

        if ($credits->isEmpty()) {
            return [];
        }

        $lastPayments = CreditPayment::withoutGlobalScopes()
            ->whereIn('credit_source_id', $credits->pluck('source_id'))
            ->whereNull('voided_at')
            ->groupBy('credit_source_id')
            ->selectRaw('credit_source_id, max(created_at) as last_at')
            ->pluck('last_at', 'credit_source_id');

        return $credits->map(function (Credit $c) use ($lastPayments) {
            $closed = $c->local_closed;
            $closedAt = null;

            if ($closed) {
                $last = $lastPayments[$c->source_id] ?? null;
                $closedAt = $last ? Carbon::parse($last) : $c->paid_updated_at;
            }

            return [
                'contract' => (string) $c->contract,
                'date' => $c->date_?->format('d.m.Y') ?? '',
                'amount' => $this->money($c->amount_local ?? $c->amount),
                'paid' => $this->money($c->paid_local ?? $c->paid),
                'remaining' => $this->money($c->local_remaining),
                'status' => $closed ? 'ýapyk' : 'açyk',
                'closed_at' => $closedAt?->format('d.m.Y') ?? '',
                'duration' => $closedAt && $c->date_ ? $this->duration($c->date_, $closedAt) : '',
                'overdue' => $this->money($closed ? 0 : $c->sms_overdue_amount),
            ];
        })->all();
    }

    /**
     * @throws ContractException
     */
    public function create(User $user, ContractData $data): CustomerContract
    {
        $this->authorize($user, 'contracts.create');
        $this->checkData($user, $data);

        $contract = DB::transaction(function () use ($user, $data) {
            $contract = new CustomerContract($data->attributes() + [
                'number' => $this->nextNumber((int) now()->year),
                'status' => CustomerContract::DRAFT,
                'template_version' => ContractPdfService::VERSION,
                'created_by' => $user->id,
                'created_by_name' => mb_substr((string) $user->full_name, 0, 255),
            ]);
            $contract->save();

            return $contract;
        });

        $this->audit->log(
            action: 'contract_created',
            category: 'contract',
            subject: $contract,
            newValues: $data->attributes(),
            extra: ['number' => $contract->number, 'filled_from_credit' => $data->creditSourceId],
            message: 'Contract created',
        );

        return $contract;
    }

    /**
     * Gives out the next number on a blank form, to be filled in by hand.
     * The number is kept on record like any contract, so it is never given twice.
     *
     * @throws ContractException
     */
    public function issueBlank(User $user, string $branch): CustomerContract
    {
        $this->authorize($user, 'contracts.create');

        $branch = trim($branch);
        if ($branch === '' || ! $user->canAccessBranch($branch)) {
            throw new ContractException('branch_not_allowed', __('validations/validations.contracts.branch_not_allowed'));
        }

        $contract = DB::transaction(function () use ($user, $branch) {
            $contract = new CustomerContract([
                'number' => $this->nextNumber((int) now()->year),
                'status' => CustomerContract::ISSUED,
                'template_version' => ContractPdfService::VERSION,
                'branch' => $branch,
                'contract_date' => now()->toDateString(),
                // Written on the paper by hand; entered here later, if at all.
                'customer_name' => '',
                'created_by' => $user->id,
                'created_by_name' => mb_substr((string) $user->full_name, 0, 255),
            ]);
            $contract->save();

            return $contract;
        });

        $this->audit->log(
            action: 'contract_issued',
            category: 'contract',
            subject: $contract,
            newValues: ['branch' => $branch],
            extra: ['number' => $contract->number],
            message: 'Blank contract issued',
        );

        return $contract;
    }

    /**
     * Changes a draft, or fills in a blank (which makes it a draft). Every
     * change is logged with what it replaced.
     *
     * @throws ContractException
     */
    public function update(User $user, CustomerContract $contract, ContractData $data): CustomerContract
    {
        $this->authorize($user, 'contracts.create');
        $this->checkData($user, $data);

        [$contract, $old, $new] = DB::transaction(function () use ($contract, $data) {
            $c = $this->lock($contract);

            if (! $c->isOpen()) {
                throw new ContractException('not_draft', __('validations/validations.contracts.not_draft'));
            }

            $c->fill($data->attributes() + ['status' => CustomerContract::DRAFT]);
            $dirty = $c->getDirty();

            if ($dirty === []) {
                return [$c, [], []];
            }

            $old = array_intersect_key($c->getOriginal(), $dirty);
            $c->save();

            return [$c, $old, array_intersect_key($c->getAttributes(), $dirty)];
        });

        if ($new !== []) {
            $this->audit->log(
                action: 'contract_updated',
                category: 'contract',
                subject: $contract,
                oldValues: $this->plain($old),
                newValues: $this->plain($new),
                extra: ['number' => $contract->number],
                message: 'Contract draft changed',
            );
        }

        return $contract;
    }

    /**
     * @throws ContractException
     */
    public function approve(User $user, CustomerContract $contract): CustomerContract
    {
        $this->authorize($user, 'contracts.approve');

        $contract = DB::transaction(function () use ($user, $contract) {
            $c = $this->lock($contract);

            if (! $c->isDraft()) {
                throw new ContractException('not_draft', __('validations/validations.contracts.not_draft'));
            }

            $c->forceFill([
                'status' => CustomerContract::APPROVED,
                'approved_by' => $user->id,
                'approved_by_name' => mb_substr((string) $user->full_name, 0, 255),
                'approved_at' => now(),
            ])->save();

            return $c;
        });

        $this->audit->log(
            action: 'contract_approved',
            category: 'contract',
            subject: $contract,
            oldValues: ['status' => CustomerContract::DRAFT],
            newValues: ['status' => CustomerContract::APPROVED],
            extra: ['number' => $contract->number],
            message: 'Contract approved',
        );

        return $contract;
    }

    /**
     * Cancels a contract that is not cancelled yet. It stays on record, with why.
     *
     * @throws ContractException
     */
    public function cancel(User $user, CustomerContract $contract, ?string $reason): CustomerContract
    {
        $this->authorize($user, 'contracts.cancel');

        $reason = trim(preg_replace('/\s+/u', ' ', (string) $reason));
        if ($reason === '') {
            throw new ContractException('cancel_reason_required', __('validations/validations.contracts.cancel_reason_required'));
        }

        [$contract, $was] = DB::transaction(function () use ($user, $contract, $reason) {
            $c = $this->lock($contract);

            if ($c->isCancelled()) {
                throw new ContractException('already_cancelled', __('validations/validations.contracts.already_cancelled'));
            }

            $was = $c->status;

            $c->forceFill([
                'status' => CustomerContract::CANCELLED,
                'cancelled_by' => $user->id,
                'cancelled_by_name' => mb_substr((string) $user->full_name, 0, 255),
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
            ])->save();

            return [$c, $was];
        });

        $this->audit->log(
            action: 'contract_cancelled',
            category: 'contract',
            subject: $contract,
            oldValues: ['status' => $was],
            newValues: ['status' => CustomerContract::CANCELLED],
            extra: ['number' => $contract->number, 'reason' => $reason],
            message: 'Contract cancelled',
            severity: 'warning',
            isSuspicious: $was === CustomerContract::APPROVED,
        );

        if ($was === CustomerContract::APPROVED) {
            $this->audit->alert(
                alertType: 'contract_cancelled',
                riskLevel: 'medium',
                message: 'An approved contract was cancelled',
                meta: ['number' => $contract->number, 'reason' => $reason],
            );
        }

        return $contract;
    }

    /**
     * The PDF of a contract. Every print is counted and logged; a draft or a
     * cancelled contract says so across its pages, and a blank carries its
     * number only.
     *
     * @return array{0: string, 1: string} file name, PDF
     */
    public function print(User $user, CustomerContract $contract): array
    {
        $mark = match (true) {
            $contract->isDraft() => self::MARK_DRAFT,
            $contract->isCancelled() => self::MARK_CANCELLED,
            default => null,
        };

        $printable = $contract->isIssued() ? ['number' => $contract->number] : $this->printable($contract);
        $pdf = $this->pdf->render($printable, $mark);

        DB::transaction(function () use ($contract) {
            $c = $this->lock($contract);
            $c->forceFill([
                'print_count' => $c->print_count + 1,
                'last_printed_at' => now(),
            ])->save();
            $contract->setRawAttributes($c->getAttributes(), true);
        });

        $this->audit->log(
            action: 'contract_printed',
            category: 'contract',
            subject: $contract,
            extra: ['number' => $contract->number, 'status' => $contract->status, 'print_count' => $contract->print_count],
            message: 'Contract printed',
        );

        return ["{$contract->number}.pdf", $pdf];
    }

    /**
     * @return array<string, mixed>
     */
    private function printable(CustomerContract $c): array
    {
        return [
            'number' => $c->number,
            'date' => $c->contract_date,
            'customer_name' => $c->customer_name,
            'passport_series' => $c->passport_series,
            'passport_number' => $c->passport_number,
            'address' => $c->address,
            'phone' => $c->phone,
            'months' => $c->months,
            'monthly_amount' => $c->monthly_amount,
            'installment_part' => $c->installment_part,
            'goods' => $c->goods ?? [],
            'history' => $c->history ?? [],
        ];
    }

    /**
     * Takes the next number of the year. Runs inside the caller's
     * transaction, so a contract that fails to save gives its number back.
     */
    private function nextNumber(int $year): string
    {
        DB::table('contract_sequences')->insertOrIgnore([
            'year' => $year,
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $last = (int) DB::table('contract_sequences')->where('year', $year)->lockForUpdate()->value('last_number');
        $next = $last + 1;

        DB::table('contract_sequences')->where('year', $year)->update([
            'last_number' => $next,
            'updated_at' => now(),
        ]);

        return sprintf('%s-%d-%07d', self::PREFIX, $year, $next);
    }

    private function lock(CustomerContract $contract): CustomerContract
    {
        return CustomerContract::query()->whereKey($contract->getKey())->lockForUpdate()->firstOrFail();
    }

    /**
     * @throws ContractException
     */
    private function authorize(User $user, string $ability): void
    {
        if (! Access::allows($user, $ability)) {
            throw new ContractException('forbidden', __('validations/validations.contracts.forbidden'));
        }
    }

    /**
     * @throws ContractException
     */
    private function checkData(User $user, ContractData $data): void
    {
        if (trim($data->customerName) === '') {
            throw new ContractException('customer_name_required', __('validations/validations.contracts.customer_name_required'));
        }

        if (trim($data->branch) === '' || ! $user->canAccessBranch($data->branch)) {
            throw new ContractException('branch_not_allowed', __('validations/validations.contracts.branch_not_allowed'));
        }

        if ($data->creditSourceId !== null && ! Credit::query()->whereKey($data->creditSourceId)->exists()) {
            throw new ContractException('credit_not_found', __('validations/validations.contracts.credit_not_found'));
        }
    }

    private function passportKey(string $passport): string
    {
        return mb_strtoupper(preg_replace('/[\s\-№]+/u', '', $passport));
    }

    private function money(mixed $v): string
    {
        $n = round((float) ($v ?? 0), 2);

        return floor($n) == $n
            ? number_format($n, 0, '.', ' ')
            : number_format($n, 2, '.', ' ');
    }

    private function duration(Carbon $from, Carbon $to): string
    {
        $months = (int) $from->copy()->startOfDay()->diffInMonths($to->copy()->startOfDay());

        return $months > 0
            ? $months . ' aý'
            : max(1, (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay())) . ' gün';
    }

    /**
     * Column values as the log stores them: lists decoded rather than JSON text.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function plain(array $values): array
    {
        foreach (['goods', 'history'] as $key) {
            if (isset($values[$key]) && is_string($values[$key])) {
                $values[$key] = json_decode($values[$key], true);
            }
        }

        return $values;
    }
}
