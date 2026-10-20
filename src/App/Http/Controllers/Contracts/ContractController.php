<?php

namespace App\Http\Controllers\Contracts;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Credit;
use App\Models\CustomerContract;
use App\Services\Contracts\ContractData;
use App\Services\Contracts\ContractException;
use App\Services\Contracts\ContractService;
use App\Services\Payments\Money;
use App\Support\Access;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContractController extends Controller
{
    private const HISTORY_KEYS = ['contract', 'date', 'amount', 'paid', 'remaining', 'status', 'closed_at', 'duration', 'overdue'];

    private const GOODS_ROWS = 7;

    public function __construct(private ContractService $contracts)
    {
    }

    public function index(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:100',
            'status' => 'nullable|in:draft,approved,cancelled',
        ]);

        $q = trim((string) $request->get('q', ''));
        $status = (string) $request->get('status', '');

        $contracts = CustomerContract::query()
            ->when($q !== '', function ($query) use ($q) {
                $like = '%' . $q . '%';
                $query->where(function ($qq) use ($like) {
                    $qq->where('number', 'ilike', $like)
                        ->orWhere('customer_name', 'ilike', $like)
                        ->orWhere('passport_number', 'ilike', $like)
                        ->orWhere('phone', 'ilike', $like);
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(20)
            ->appends($request->query());

        return view('pages.contracts.index', compact('contracts', 'q', 'status'));
    }

    /**
     * Find the customer, then fill the form from their credit or by hand.
     */
    public function create(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:100',
            'credit' => 'nullable|integer|min:1',
        ]);

        $q = trim((string) $request->get('q', ''));

        $credits = $q !== ''
            ? Credit::query()->where('active', true)->matching($q)->orderByDesc('date_')->paginate(10)->appends($request->query())
            : null;

        $form = null;
        $credit = null;

        if ($request->filled('credit')) {
            // The branch scope applies: only a credit the user may see fills the form.
            $credit = Credit::query()->where('active', true)->findOrFail($request->integer('credit'));
            $form = $this->contracts->prefill($credit);
        } elseif ($request->boolean('manual')) {
            $form = ['history' => []];
        }

        if ($form !== null) {
            $form += ['contract_date' => now()->toDateString(), 'goods' => []];
        }

        return view('pages.contracts.create', [
            'q' => $q,
            'credits' => $credits,
            'credit' => $credit,
            'form' => $form,
            'branches' => $this->branches(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->data($request);

        try {
            $contract = $this->contracts->create(Auth::user(), $data);
        } catch (ContractException $e) {
            return back()->withInput()->with('warning', $e->getMessage());
        }

        return redirect()->route('contracts.show', $contract)->with('success', __('pages/contracts.created') . ': ' . $contract->number);
    }

    public function show(CustomerContract $contract)
    {
        $user = Auth::user();

        $changes = ActivityLog::query()
            ->where('subject_type', CustomerContract::class)
            ->where('subject_id', (string) $contract->id)
            ->where('category', 'contract')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return view('pages.contracts.show', [
            'contract' => $contract,
            'changes' => $changes,
            'branches' => $this->branches(),
            'canEdit' => $contract->isDraft() && Access::allows($user, 'contracts.create'),
            'canApprove' => $contract->isDraft() && Access::allows($user, 'contracts.approve'),
            'canCancel' => ! $contract->isCancelled() && Access::allows($user, 'contracts.cancel'),
        ]);
    }

    public function update(Request $request, CustomerContract $contract)
    {
        $data = $this->data($request);

        try {
            $contract = $this->contracts->update(Auth::user(), $contract, $data);
        } catch (ContractException $e) {
            return back()->withInput()->with('warning', $e->getMessage());
        }

        $changed = $contract->wasChanged();

        return redirect()->route('contracts.show', $contract)
            ->with($changed ? 'success' : 'info', __($changed ? 'pages/contracts.updated' : 'pages/contracts.nothing_changed'));
    }

    public function approve(CustomerContract $contract)
    {
        try {
            $this->contracts->approve(Auth::user(), $contract);
        } catch (ContractException $e) {
            return back()->with('warning', $e->getMessage());
        }

        return back()->with('success', __('pages/contracts.approved'));
    }

    public function cancel(Request $request, CustomerContract $contract)
    {
        $request->validate(['cancel_reason' => 'nullable|string|max:1000']);

        try {
            $this->contracts->cancel(Auth::user(), $contract, $request->input('cancel_reason'));
        } catch (ContractException $e) {
            return back()->withInput()->with('warning', $e->getMessage());
        }

        return back()->with('success', __('pages/contracts.cancelled'));
    }

    public function print(CustomerContract $contract)
    {
        [$name, $pdf] = $this->contracts->print(Auth::user(), $contract);

        return $this->pdf($pdf, $name);
    }

    public function blank()
    {
        return $this->pdf($this->contracts->blank(), 'shertnama.pdf');
    }

    private function pdf(string $pdf, string $name)
    {
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            // Opened in the browser, to be printed from there.
            'Content-Disposition' => 'inline; filename="' . $name . '"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /**
     * Branches the user may make a contract for.
     *
     * @return string[]
     */
    private function branches(): array
    {
        return Credit::query()
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch')
            ->all();
    }

    private function data(Request $request): ContractData
    {
        $amount = function (string $attribute, $value, $fail) {
            if ($value !== null && $value !== '' && Money::parse($value) <= 0 && ! preg_match('/^\s*0+([.,]0+)?\s*$/', (string) $value)) {
                $fail(__('pages/contracts.validation.amount', ['attribute' => $this->attributeName($attribute)]));
            }
        };

        $v = $request->validate([
            'branch' => 'required|string|max:50',
            'contract_date' => 'required|date',
            'customer_name' => 'required|string|max:255',
            'passport_series' => 'nullable|string|max:20',
            'passport_number' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:100',
            'months' => 'nullable|integer|between:1,120',
            'monthly_amount' => ['nullable', 'string', 'max:30', $amount],
            'installment_part' => 'nullable|string|max:100',
            'credit_source_id' => 'nullable|integer|min:1',
            'goods' => 'nullable|array|max:' . self::GOODS_ROWS,
            'goods.*.name' => 'nullable|string|max:100',
            'goods.*.quantity' => 'nullable|string|max:20',
            'goods.*.price' => 'nullable|string|max:30',
            'goods.*.total' => 'nullable|string|max:30',
            'history' => 'nullable|array|max:200',
            'history.*' => 'array',
            'history.*.*' => 'nullable|string|max:50',
        ], $this->messages(), $this->attributes());

        $goods = collect($v['goods'] ?? [])
            ->map(fn ($g) => [
                'name' => trim((string) ($g['name'] ?? '')),
                'quantity' => $this->clean($g['quantity'] ?? null),
                'price' => $this->number($g['price'] ?? null),
                'total' => $this->number($g['total'] ?? null),
            ])
            ->filter(fn ($g) => $g['name'] !== '')
            ->values()
            ->all();

        $history = collect($v['history'] ?? [])
            ->map(fn ($row) => collect(self::HISTORY_KEYS)->mapWithKeys(fn ($k) => [$k => trim((string) ($row[$k] ?? ''))])->all())
            ->filter(fn ($row) => implode('', $row) !== '')
            ->values()
            ->all();

        $monthly = $this->clean($v['monthly_amount'] ?? null);

        return new ContractData(
            branch: trim($v['branch']),
            contractDate: Carbon::parse($v['contract_date'])->startOfDay(),
            customerName: trim(preg_replace('/\s+/u', ' ', $v['customer_name'])),
            passportSeries: $this->clean($v['passport_series'] ?? null),
            passportNumber: $this->clean($v['passport_number'] ?? null),
            address: $this->clean($v['address'] ?? null),
            phone: $this->clean($v['phone'] ?? null),
            months: isset($v['months']) ? (int) $v['months'] : null,
            monthlyAmount: $monthly !== null ? Money::parse($monthly) : null,
            installmentPart: $this->clean($v['installment_part'] ?? null),
            goods: $goods,
            history: $history,
            creditSourceId: isset($v['credit_source_id']) ? (int) $v['credit_source_id'] : null,
        );
    }

    private function clean(mixed $v): ?string
    {
        $v = trim(preg_replace('/\s+/u', ' ', (string) $v));

        return $v === '' ? null : $v;
    }

    /**
     * An amount as typed ("1 500,50") stored plainly, or the text as it is when it is not one.
     */
    private function number(mixed $v): ?string
    {
        $v = $this->clean($v);

        if ($v === null) {
            return null;
        }

        return preg_match('/^[\d\s.,]+$/u', $v) ? Money::format(Money::parse($v)) : $v;
    }

    private function messages(): array
    {
        return collect(['required', 'max', 'date', 'integer', 'between', 'array'])
            ->mapWithKeys(fn ($rule) => [$rule => __('pages/contracts.validation.' . $rule)])
            ->all();
    }

    private function attributes(): array
    {
        $names = [];
        foreach (['branch', 'contract_date', 'customer_name', 'passport_series', 'passport_number', 'address', 'phone', 'months', 'monthly_amount', 'installment_part'] as $field) {
            $names[$field] = __('pages/contracts.fields.' . $field);
        }

        $names['goods.*.name'] = __('pages/contracts.fields.goods_name');
        $names['goods.*.quantity'] = __('pages/contracts.fields.quantity');
        $names['goods.*.price'] = __('pages/contracts.fields.price');
        $names['goods.*.total'] = __('pages/contracts.fields.total');
        $names['goods'] = __('pages/contracts.section_goods');
        $names['history'] = __('pages/contracts.section_history');

        return $names;
    }

    private function attributeName(string $attribute): string
    {
        return $this->attributes()[$attribute] ?? $attribute;
    }
}
