<?php

namespace App\Http\Controllers\Reports;

use App\Exports\CustomerStatementExport;
use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\CreditPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CustomerStatementReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'contract' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:100',
            'customer' => 'nullable|string|max:255',
            'branch' => 'nullable|string|max:100',
        ]);

        $contract = trim((string) $request->get('contract', ''));
        $phone = trim((string) $request->get('phone', ''));
        $customer = trim((string) $request->get('customer', ''));
        $branch = trim((string) $request->get('branch', ''));

        $branches = $this->branches();

        $credit = null;
        $matches = collect();
        $payments = collect();
        $statementRows = collect();
        $summary = null;

        if ($contract !== '') {
            $credit = $this->findCreditByContract($contract, $branch);
        } elseif ($phone !== '' || $customer !== '') {
            $matches = $this->searchCredits($phone, $customer, $branch, $request);

            if ($matches->total() === 1) {
                $credit = $matches->first();
                $matches = collect();
            }
        }

        if ($credit) {
            [$payments, $statementRows, $summary] = $this->buildStatement($credit);
        }

        return view('pages.reports.customer-statement.index', [
            'credit' => $credit,
            'matches' => $matches,
            'payments' => $payments,
            'statementRows' => $statementRows,
            'summary' => $summary,
            'branches' => $branches,
            'contract' => $contract,
            'phone' => $phone,
            'customer' => $customer,
            'branch' => $branch,
        ]);
    }

    public function export(Request $request)
    {
        $request->validate([
            'contract' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:100',
            'customer' => 'nullable|string|max:255',
            'branch' => 'nullable|string|max:100',
        ]);

        $contract = trim((string) $request->get('contract', ''));

        if ($contract === '') {
            return back()->with('error', 'Please select a customer statement before exporting.');
        }

        return Excel::download(
            new CustomerStatementExport($request),
            'customer-statement-' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    private function branches()
    {
        return Credit::query()
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch');
    }

    private function findCreditByContract(string $contract, string $branch): ?Credit
    {
        return Credit::query()
            ->where('contract', $contract)
            ->when($branch !== '', fn ($q) => $q->where('branch', $branch))
            ->orderByDesc('source_id')
            ->first([
                'source_id',
                'logicalref',
                'contract',
                'name',
                'phone',
                'passport',
                'branch',
                'clientref',
                'date_',
                'amount_local',
                'amount',
                'paid_local',
                'paid',
                'status',
                'note',
            ]);
    }

    private function searchCredits(string $phone, string $customer, string $branch, Request $request)
    {
        return Credit::query()
            ->when($phone !== '', fn ($q) => $q->where('phone', 'ilike', '%' . $phone . '%'))
            ->when($customer !== '', fn ($q) => $q->where('name', 'ilike', '%' . $customer . '%'))
            ->when($branch !== '', fn ($q) => $q->where('branch', $branch))
            ->orderBy('name')
            ->orderByDesc('source_id')
            ->paginate(
                perPage: 50,
                columns: [
                    'source_id',
                    'logicalref',
                    'contract',
                    'name',
                    'phone',
                    'passport',
                    'branch',
                    'clientref',
                    'date_',
                    'amount_local',
                    'amount',
                    'paid_local',
                    'paid',
                    'status',
                    'note',
                ],
                pageName: 'page'
            )
            ->appends($request->query());
    }

    private function buildStatement(Credit $credit): array
    {
        $payments = CreditPayment::query()
            ->where('credit_source_id', (int) $credit->source_id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $amount = (float) ($credit->amount_local ?? $credit->amount ?? 0);
        $paid = (float) ($credit->paid_local ?? $credit->paid ?? 0);
        $remaining = round(max($amount - $paid, 0), 2);

        $balance = round($amount, 2);
        $statementRows = collect();

        $statementRows->push((object) [
            'date' => $credit->date_ ? Carbon::parse($credit->date_) : null,
            'type' => 'Credit Created',
            'description' => 'Credit amount created',
            'debit' => round($amount, 2),
            'credit' => 0,
            'change' => 0,
            'net' => 0,
            'balance' => $balance,
            'method' => '-',
            'cashier' => '-',
            'note' => $credit->note,
            'is_voided' => false,
            'is_corrected' => false,
        ]);

        foreach ($payments as $payment) {
            $gross = (float) ($payment->pay_amount ?? 0);
            $change = (float) ($payment->change_amount ?? 0);
            $net = round($gross - $change, 2);

            $isVoided = ! empty($payment->voided_at);
            $isCorrected = ! empty($payment->corrected_at);

            if (! $isVoided) {
                $balance = round(max($balance - $net, 0), 2);
            }

            $statementRows->push((object) [
                'date' => $payment->created_at ? Carbon::parse($payment->created_at) : null,
                'type' => $isVoided ? 'Voided Payment' : ($isCorrected ? 'Corrected Payment' : 'Payment'),
                'description' => 'Payment #' . $payment->id,
                'debit' => 0,
                'credit' => $isVoided ? 0 : $net,
                'change' => round($change, 2),
                'net' => $isVoided ? 0 : $net,
                'balance' => $balance,
                'method' => strtoupper((string) ($payment->method ?? '-')),
                'cashier' => $payment->created_by_name ?? '-',
                'note' => $payment->note,
                'is_voided' => $isVoided,
                'is_corrected' => $isCorrected,
            ]);
        }

        $summary = [
            'total_debt' => round($amount, 2),
            'paid' => round($paid, 2),
            'remaining' => $remaining,
            'payment_count' => $payments->whereNull('voided_at')->count(),
            'voided_count' => $payments->whereNotNull('voided_at')->count(),
            'last_payment_at' => optional(
                $payments->whereNull('voided_at')->sortByDesc('created_at')->first()
            )->created_at,
        ];

        return [$payments, $statementRows, $summary];
    }
}