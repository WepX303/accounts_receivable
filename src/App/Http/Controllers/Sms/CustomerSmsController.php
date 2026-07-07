<?php

namespace App\Http\Controllers\Sms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sms\PreviewCustomerSmsRequest;
use App\Http\Requests\Sms\SendCustomerSmsRequest;
use App\Models\Credit;
use App\Services\Sms\CustomerSmsTemplateService;
use App\Services\Sms\SmsApiClientService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Exports\SmsPreviewExport;
use App\Services\Sms\SmsPhoneNormalizerService;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class CustomerSmsController extends Controller
{

    private function resolveStartTime(Request $request): string
    {
        $scheduleType = $request->input('schedule_type', 'after_2m');

        return match ($scheduleType) {
            'now' => now()->format('Y-m-d H:i:s'),
            'after_2m' => now()->addMinutes(2)->format('Y-m-d H:i:s'),
            'after_5m' => now()->addMinutes(5)->format('Y-m-d H:i:s'),
            'after_10m' => now()->addMinutes(10)->format('Y-m-d H:i:s'),
            'custom' => \Carbon\Carbon::parse($request->input('scheduled_at'))->format('Y-m-d H:i:s'),
            default => now()->addMinutes(2)->format('Y-m-d H:i:s'),
        };
    }

    private function buildCustomerStatusData(Credit $customer): array
    {
        $remaining = (float) ($customer->local_remaining ?? 0);

        if ($remaining <= 0) {
            return [
                'status_label' => __('messages.closed'),
                'status_class' => 'success',
                'day_info' => '-',
            ];
        }

        if (! $customer->date_) {
            return [
                'status_label' => __('messages.no_due_date'),
                'status_class' => 'dark',
                'day_info' => '-',
            ];
        }

        $overdueAmount = (float) $customer->sms_overdue_amount;

        if ($overdueAmount > 0) {
            $overdueDays = 0;

            if ($customer->willpaiddate) {
                $willPaidDate = \Carbon\Carbon::parse($customer->willpaiddate)->startOfDay();
                $today = now()->startOfDay();

                if ($willPaidDate->lt($today)) {
                    $overdueDays = $willPaidDate->diffInDays($today);
                }
            }

            return [
                'status_label' => __('messages.overdue'),
                'status_class' => 'danger',
                'day_info' => $overdueDays . ' ' . __('messages.days_overdue') . ' / ' . number_format($overdueAmount, 2) . ' TMT',
            ];
        }

        $start = $customer->date_->copy()->startOfDay();
        $today = now()->startOfDay();

        $nextDueDate = null;

        for ($i = 1; $i <= 6; $i++) {
            $dueDate = $start->copy()->addMonthsNoOverflow($i)->startOfDay();

            if ($dueDate->gte($today)) {
                $nextDueDate = $dueDate;
                break;
            }
        }

        if (! $nextDueDate) {
            return [
                'status_label' => __('messages.waiting'),
                'status_class' => 'secondary',
                'day_info' => '-',
            ];
        }

        if ($nextDueDate->equalTo($today)) {
            return [
                'status_label' => __('messages.due_today'),
                'status_class' => 'info',
                'day_info' => __('messages.today'),
            ];
        }

        $daysLater = $today->diffInDays($nextDueDate);

        if ($daysLater <= 3) {
            return [
                'status_label' => __('messages.approaching'),
                'status_class' => 'warning',
                'day_info' => $daysLater . ' ' . __('messages.days_later'),
            ];
        }

        return [
            'status_label' => __('messages.waiting'),
            'status_class' => 'secondary',
            'day_info' => $daysLater . ' ' . __('messages.days_later'),
        ];
    }

    private function baseSmsQuery()
    {
        return Credit::query()
            ->where('active', true)
            ->where('is_blocked', 0)
            ->whereRaw('COALESCE(amount_local, amount, 0) > COALESCE(paid_local, paid, 0)');
    }

    private function applySmsFilters($query, Request $request)
    {
        if (!$request->filled('min_remaining') && !$request->filled('max_remaining')) {
            $query->whereRaw(
                '(COALESCE(amount_local, amount, 0) - COALESCE(paid_local, paid, 0)) >= ?',
                [10]
            );
        }

        if ($request->filled('branch')) {
            $branches = collect($request->input('branch', []))
                ->filter()
                ->values()
                ->all();

            if (!empty($branches)) {
                $query->whereIn('branch', $branches);
            }
        }

        if ($request->filled('name')) {
            $name = trim($request->string('name')->toString());
            $query->where('name', 'like', "%{$name}%");
        }

        if ($request->filled('phone')) {
            $phone = trim($request->string('phone')->toString());
            $query->where('phone', 'like', "%{$phone}%");
        }

        if ($request->filled('passport')) {
            $passport = trim($request->string('passport')->toString());
            $query->where('passport', 'like', "%{$passport}%");
        }

        if ($request->filled('contract')) {
            $contract = trim($request->string('contract')->toString());
            $query->where('contract', 'like', "%{$contract}%");
        }

        if ($request->filled('phone_status')) {

            if ($request->phone_status === 'has_phone') {
                $query->whereNotNull('phone')
                    ->where('phone', '!=', '');
            }

            if ($request->phone_status === 'no_phone') {
                $query->where(function ($q) {
                    $q->whereNull('phone')
                        ->orWhere('phone', '');
                });
            }

            if ($request->phone_status === 'multi_phone') {
                $query->where('phone', 'like', '% %');
            }
        }

        if ($request->filled('payment_date_from')) {
            $query->whereDate(
                'willpaiddate',
                '>=',
                $request->payment_date_from
            );
        }

        if ($request->filled('payment_date_to')) {
            $query->whereDate(
                'willpaiddate',
                '<=',
                $request->payment_date_to
            );
        }

        $statusType = $request->string('status_type')->toString();

        if ($statusType === 'overdue') {
            $today = now()->toDateString();

            $query->whereRaw("
            (
                LEAST(GREATEST(FLOOR((?::date - date_::date) / 30), 0), 6)
                * ROUND((COALESCE(amount_local, amount, 0) / 6)::numeric, 2)
            ) > COALESCE(paid_local, paid, 0)
        ", [$today]);
        }

        if ($statusType === 'due_today') {
            $today = now()->toDateString();

            $query->whereRaw("
            date_ IS NOT NULL
            AND (
                date_::date + (
                    LEAST(GREATEST(FLOOR((?::date - date_::date) / 30), 0), 5) + 1
                ) * INTERVAL '1 month'
            )::date = ?::date
            AND (
                LEAST(GREATEST(FLOOR((?::date - date_::date) / 30), 0), 6)
                * ROUND((COALESCE(amount_local, amount, 0) / 6)::numeric, 2)
            ) <= COALESCE(paid_local, paid, 0)
        ", [$today, $today, $today]);
        }

        if ($statusType === 'due_in_days') {
            $days = max((int) $request->input('due_days', 3), 1);
            $today = now()->toDateString();
            $toDate = now()->copy()->addDays($days)->toDateString();

            $query->whereRaw("
            date_ IS NOT NULL
            AND (
                date_::date + (
                    LEAST(GREATEST(FLOOR((?::date - date_::date) / 30), 0), 5) + 1
                ) * INTERVAL '1 month'
            )::date BETWEEN ?::date AND ?::date
            AND (
                LEAST(GREATEST(FLOOR((?::date - date_::date) / 30), 0), 6)
                * ROUND((COALESCE(amount_local, amount, 0) / 6)::numeric, 2)
            ) <= COALESCE(paid_local, paid, 0)
        ", [$today, $today, $toDate, $today]);
        }

        if ($request->filled('min_remaining')) {
            $query->whereRaw(
                '(COALESCE(amount_local, amount, 0) - COALESCE(paid_local, paid, 0)) >= ?',
                [(float) $request->input('min_remaining')]
            );
        }

        if ($request->filled('max_remaining')) {
            $query->whereRaw(
                '(COALESCE(amount_local, amount, 0) - COALESCE(paid_local, paid, 0)) <= ?',
                [(float) $request->input('max_remaining')]
            );
        }

        return $query;
    }

    public function index(Request $request): View
    {
        if (! $request->has('preview')) {
            session()->forget([
                'sms_preview_rows',
                'sms_preview_message',
                'sms_schedule_type',
                'sms_scheduled_at',
                'sms_export_skipped_rows',
            ]);
        }

        $query = $this->baseSmsQuery();

        $query = $this->applySmsFilters($query, $request);

        $statusType = $request->string('status_type')->toString();

        $customers = $query
            ->orderByRaw('CASE WHEN willpaiddate IS NULL THEN 1 ELSE 0 END')
            ->orderBy('willpaiddate', 'asc')
            ->orderBy('logicalref', 'desc')
            ->paginate(20)
            ->appends($request->query());

        $customers->getCollection()->transform(function (Credit $customer) {
            $statusData = $this->buildCustomerStatusData($customer);

            $customer->status_label = $statusData['status_label'];
            $customer->status_class = $statusData['status_class'];
            $customer->day_info = $statusData['day_info'];

            return $customer;
        });


        $branches = Credit::query()
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch');


        $previewRows = session('sms_preview_rows', []);
        $previewMessage = session('sms_preview_message', '');
        $previewScheduleType = session('sms_schedule_type', 'after_2m');
        $previewScheduledAt = session('sms_scheduled_at', '');


        return view('pages.sms.index', [
            'customers' => $customers,
            'branches' => $branches,
            'statusType' => $statusType,
            'previewRows' => $previewRows,
            'previewMessage' => $previewMessage,
            'previewScheduleType' => $previewScheduleType,
            'previewScheduledAt' => $previewScheduledAt,
        ]);
    }

    public function preview(
        PreviewCustomerSmsRequest $request,
        CustomerSmsTemplateService $templateService
    ) {

        $previewMode = $request->input('preview_mode', 'selected');

        if ($previewMode === 'filtered') {
            $customers = $this->applySmsFilters($this->baseSmsQuery(), $request)
                ->orderBy('logicalref')
                ->get();
        } else {
            $selectedIds = collect($request->input('selected_customers', []))
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values();

            $customers = Credit::query()
                ->whereIn('source_id', $selectedIds)
                ->orderBy('logicalref')
                ->get();
        }

        if ($customers->isEmpty()) {
            return redirect()
                ->route('sms.index')
                ->withErrors([
                    'selected_customers' => __('messages.no_valid_customer_found'),
                ])
                ->withInput();
        }

        $message = trim($request->input('message'));

        $previewRows = $customers->map(function (Credit $credit) use ($templateService, $message) {
            return [
                'logicalref' => $credit->logicalref,
                'name' => $credit->name,
                'phone' => $credit->phone,
                'contract' => $credit->contract,
                'branch' => $credit->branch,
                'message' => $templateService->render($message, $credit),
            ];
        })->values()->all();

        session()->put('sms_preview_rows', $previewRows);
        session()->put('sms_preview_message', $message);
        session()->put('sms_schedule_type', $request->input('schedule_type', 'after_2m'));
        session()->put('sms_scheduled_at', $request->input('scheduled_at'));
        session()->forget('sms_export_skipped_rows');

        $query = array_merge(
            request()->query(),
            ['preview' => 1]
        );

        return redirect()
            ->route('sms.index', $query)
            ->with('success', __('messages.preview_generated_successfully'));
    }
    public function exportPreview(
        Request $request,
        SmsPhoneNormalizerService $phoneNormalizerService
    ) {
        $previewRows = session('sms_preview_rows', []);

        if (empty($previewRows)) {
            return redirect()
                ->route('sms.index', $request->query())
                ->withErrors([
                    'export' => __('messages.no_preview_data_to_export'),
                ]);
        }

        $exportRows = [];
        $skippedRows = [];

        foreach ($previewRows as $row) {
            $phoneText = trim((string) ($row['phone'] ?? ''));
            $messageText = $row['message'] ?? '';

            if ($phoneText === '') {
                $skippedRows[] = [
                    'name' => $row['name'] ?? '-',
                    'phone' => '-',
                    'contract' => $row['contract'] ?? '-',
                    'reason' => 'empty',
                ];
                continue;
            }

            $phoneParts = preg_split('/\s+/', $phoneText, -1, PREG_SPLIT_NO_EMPTY);

            foreach ($phoneParts as $phonePart) {
                $normalized = $phoneNormalizerService->normalize($phonePart);

                if (!$normalized['valid']) {
                    $skippedRows[] = [
                        'name' => $row['name'] ?? '-',
                        'phone' => $phonePart,
                        'contract' => $row['contract'] ?? '-',
                        'reason' => $normalized['reason'],
                    ];
                    continue;
                }

                $exportRows[] = [
                    $normalized['phone'],
                    $messageText,
                ];
            }
        }

        if (empty($exportRows)) {
            session()->put('sms_export_skipped_rows', $skippedRows);

            return redirect()
                ->route('sms.index', $request->query())
                ->withErrors([
                    'export' => __('messages.no_valid_phone_for_export'),
                ]);
        }

        session()->put('sms_export_skipped_rows', $skippedRows);

        return Excel::download(
            new SmsPreviewExport($exportRows),
            'sms-preview-' . now()->format('Y-m-d-H-i-s') . '.xlsx'
        );
    }

    //
    public function send(
        SendCustomerSmsRequest $request,
        CustomerSmsTemplateService $templateService,
        SmsApiClientService $smsApiClientService
    ) {
        $selectedIds = collect($request->input('selected_customers', []))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $customers = Credit::query()
            ->whereIn('source_id', $selectedIds)
            ->orderBy('logicalref')
            ->get();

        if ($customers->isEmpty()) {
            return redirect()
                ->route('sms.index')
                ->withErrors([
                    'selected_customers' => __('messages.no_valid_customer_found'),
                ])
                ->withInput();
        }

        $messageTemplate = trim($request->input('message'));

        $messages = [];
        $skippedNoPhone = 0;

        foreach ($customers as $credit) {
            $phone = trim((string) $credit->phone);

            if ($phone === '') {
                $skippedNoPhone++;
                continue;
            }

            $messages[] = [
                'customer_logicalref' => (int) $credit->logicalref,
                'phone' => $phone,
                'content' => $templateService->render($messageTemplate, $credit),
                'meta' => [
                    'name' => $credit->name,
                    'contract' => $credit->contract,
                    'branch' => $credit->branch,
                ],
            ];
        }

        if (empty($messages)) {
            return redirect()
                ->route('sms.index', request()->query())
                ->withErrors([
                    'selected_customers' => __('messages.no_customer_with_phone'),
                ])
                ->withInput();
        }

        $payload = [
            'source' => 'accounts_receivable',
            'service_name' => 'DISTRIBUTION_KREDIT',
            'distribution_name' => 'Accounts Receivable SMS - ' . now()->format('Y-m-d H:i:s'),
            'start_time' => $this->resolveStartTime($request),
            'created_by' => [
                'id' => auth()->id(),
                'name' => auth()->user()?->full_name,
                'email' => auth()->user()?->email,
                'phone' => auth()->user()?->phonenumber,
                'role' => auth()->user()?->role?->value,
            ],
            'messages' => $messages,
        ];

        Log::info('SMS API payload', [
            'source' => $payload['source'],
            'service_name' => $payload['service_name'],
            'distribution_name' => $payload['distribution_name'],
            'start_time' => $payload['start_time'],
            'message_count' => count($payload['messages']),
        ]);

        try {
            $response = $smsApiClientService->sendDistribution($payload);
            Log::info('SMS API response', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            if (!$response->successful()) {
                return redirect()
                    ->route('sms.index', request()->query())
                    ->withErrors([
                        'api' => __('messages.sms_api_request_failed') . ' (' . $response->status() . ')',
                    ])
                    ->withInput();
            }

            $json = $response->json();

            return redirect()
                ->route('sms.index', request()->query())
                ->with('success', __('messages.sms_send_request_success'))
                ->with('sms_api_result', $json)
                ->with('sms_skipped_no_phone', $skippedNoPhone);
        } catch (Throwable $e) {
            return redirect()
                ->route('sms.index', request()->query())
                ->withErrors([
                    'api' => __('messages.sms_api_unreachable') . ' ' . $e->getMessage(),
                ])
                ->withInput();
        }
    }

    public function clearPreview(Request $request)
    {
        session()->forget([
            'sms_preview_rows',
            'sms_preview_message',
            'sms_schedule_type',
            'sms_scheduled_at',
            'sms_export_skipped_rows',
        ]);

        return redirect()
            ->route('sms.index', $request->query())
            ->with('success', __('messages.preview_cleared'));
    }
}
