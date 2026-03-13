<?php

namespace App\Http\Controllers\Api\V1\Logs;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\LoginHistory;
use App\Models\SecurityAlert;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiLogsController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'type' => ['nullable', 'in:all,activity,login,alert'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $q = trim((string) $request->input('q', ''));
        $type = (string) $request->input('type', 'all');
        $perPage = (int) $request->input('per_page', 20);

        $dateFrom = $request->filled('date_from')
            ? $request->date('date_from')->startOfDay()
            : null;

        $dateTo = $request->filled('date_to')
            ? $request->date('date_to')->endOfDay()
            : null;

        $items = collect();

        if ($type === 'all' || $type === 'activity') {
            $activityLogs = ActivityLog::query()
                ->when($q !== '', function ($query) use ($q) {
                    $like = '%' . $q . '%';

                    $query->where(function ($sub) use ($like) {
                        $sub->where('action', 'ilike', $like)
                            ->orWhere('category', 'ilike', $like)
                            ->orWhere('severity', 'ilike', $like)
                            ->orWhere('message', 'ilike', $like)
                            ->orWhere('user_name', 'ilike', $like)
                            ->orWhere('user_email', 'ilike', $like)
                            ->orWhere('user_phone', 'ilike', $like)
                            ->orWhere('user_role', 'ilike', $like)
                            ->orWhere('ip_address', 'ilike', $like)
                            ->orWhere('browser', 'ilike', $like)
                            ->orWhere('platform', 'ilike', $like)
                            ->orWhere('route_name', 'ilike', $like)
                            ->orWhere('subject_type', 'ilike', $like)
                            ->orWhere('subject_id', 'ilike', $like);
                    });
                })
                ->when($dateFrom && $dateTo, function ($query) use ($dateFrom, $dateTo) {
                    $query->whereBetween('created_at', [$dateFrom, $dateTo]);
                })
                ->latest('created_at')
                ->get()
                ->map(function ($row) {
                    return [
                        'type' => 'activity',
                        'id' => (int) $row->id,
                        'created_at' => optional($row->created_at)?->format('Y-m-d H:i:s'),
                        'main_title' => $row->action,
                        'sub_title' => $row->category,
                        'user_text' => $row->user_name ?: $row->user_email ?: '-',
                        'status_text' => $row->is_success ? 'success' : 'failed',
                        'level_text' => $row->severity ?: 'info',
                        'ip_address' => $row->ip_address,
                        'message' => $row->message,
                        'raw' => [
                            'id' => $row->id,
                            'user_id' => $row->user_id,
                            'user_name' => $row->user_name,
                            'user_email' => $row->user_email,
                            'user_phone' => $row->user_phone,
                            'user_role' => $row->user_role,
                            'action' => $row->action,
                            'category' => $row->category,
                            'severity' => $row->severity,
                            'is_suspicious' => $row->is_suspicious,
                            'subject_type' => $row->subject_type,
                            'subject_id' => $row->subject_id,
                            'ip_address' => $row->ip_address,
                            'user_agent' => $row->user_agent,
                            'device_type' => $row->device_type,
                            'browser' => $row->browser,
                            'platform' => $row->platform,
                            'http_method' => $row->http_method,
                            'url' => $row->url,
                            'route_name' => $row->route_name,
                            'is_success' => $row->is_success,
                            'message' => $row->message,
                            'old_values' => $row->old_values,
                            'new_values' => $row->new_values,
                            'extra' => $row->extra,
                            'created_at' => optional($row->created_at)?->format('Y-m-d H:i:s'),
                        ],
                    ];
                });

            $items = $items->merge($activityLogs);
        }

        if ($type === 'all' || $type === 'login') {
            $loginHistories = LoginHistory::query()
                ->when($q !== '', function ($query) use ($q) {
                    $like = '%' . $q . '%';

                    $query->where(function ($sub) use ($like) {
                        $sub->where('login_value', 'ilike', $like)
                            ->orWhere('status', 'ilike', $like)
                            ->orWhere('fail_reason', 'ilike', $like)
                            ->orWhere('message', 'ilike', $like)
                            ->orWhere('ip_address', 'ilike', $like)
                            ->orWhere('browser', 'ilike', $like)
                            ->orWhere('platform', 'ilike', $like)
                            ->orWhere('device_type', 'ilike', $like);
                    });
                })
                ->when($dateFrom && $dateTo, function ($query) use ($dateFrom, $dateTo) {
                    $query->whereBetween('created_at', [$dateFrom, $dateTo]);
                })
                ->latest('created_at')
                ->get()
                ->map(function ($row) {
                    return [
                        'type' => 'login',
                        'id' => (int) $row->id,
                        'created_at' => optional($row->created_at)?->format('Y-m-d H:i:s'),
                        'main_title' => $row->status,
                        'sub_title' => $row->fail_reason,
                        'user_text' => $row->login_value ?: '-',
                        'status_text' => $row->status ?: '-',
                        'level_text' => $row->is_suspicious ? 'warning' : 'info',
                        'ip_address' => $row->ip_address,
                        'message' => $row->message,
                        'raw' => [
                            'id' => $row->id,
                            'user_id' => $row->user_id,
                            'login_value' => $row->login_value,
                            'status' => $row->status,
                            'fail_reason' => $row->fail_reason,
                            'ip_address' => $row->ip_address,
                            'user_agent' => $row->user_agent,
                            'device_type' => $row->device_type,
                            'browser' => $row->browser,
                            'platform' => $row->platform,
                            'is_suspicious' => $row->is_suspicious,
                            'message' => $row->message,
                            'created_at' => optional($row->created_at)?->format('Y-m-d H:i:s'),
                        ],
                    ];
                });

            $items = $items->merge($loginHistories);
        }

        if ($type === 'all' || $type === 'alert') {
            $securityAlerts = SecurityAlert::query()
                ->when($q !== '', function ($query) use ($q) {
                    $like = '%' . $q . '%';

                    $query->where(function ($sub) use ($like) {
                        $sub->where('alert_type', 'ilike', $like)
                            ->orWhere('risk_level', 'ilike', $like)
                            ->orWhere('message', 'ilike', $like)
                            ->orWhere('ip_address', 'ilike', $like);
                    });
                })
                ->when($dateFrom && $dateTo, function ($query) use ($dateFrom, $dateTo) {
                    $query->whereBetween('created_at', [$dateFrom, $dateTo]);
                })
                ->latest('created_at')
                ->get()
                ->map(function ($row) {
                    return [
                        'type' => 'alert',
                        'id' => (int) $row->id,
                        'created_at' => optional($row->created_at)?->format('Y-m-d H:i:s'),
                        'main_title' => $row->alert_type,
                        'sub_title' => $row->risk_level,
                        'user_text' => $row->user_id ? 'User #' . $row->user_id : '-',
                        'status_text' => $row->is_resolved ? 'resolved' : 'open',
                        'level_text' => $row->risk_level ?: 'medium',
                        'ip_address' => $row->ip_address,
                        'message' => $row->message,
                        'raw' => [
                            'id' => $row->id,
                            'user_id' => $row->user_id,
                            'alert_type' => $row->alert_type,
                            'risk_level' => $row->risk_level,
                            'is_resolved' => $row->is_resolved,
                            'ip_address' => $row->ip_address,
                            'message' => $row->message,
                            'meta' => $row->meta,
                            'created_at' => optional($row->created_at)?->format('Y-m-d H:i:s'),
                            'resolved_at' => optional($row->resolved_at)?->format('Y-m-d H:i:s'),
                        ],
                    ];
                });

            $items = $items->merge($securityAlerts);
        }

        $items = $items->sortByDesc('created_at')->values();

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $pageItems = $items
            ->slice(($currentPage - 1) * $perPage, $perPage)
            ->values();

        $paginator = new LengthAwarePaginator(
            $pageItems,
            $items->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $stats = [
            'total' => $items->count(),
            'activity' => $items->where('type', 'activity')->count(),
            'login' => $items->where('type', 'login')->count(),
            'alert' => $items->where('type', 'alert')->count(),

            'success_count' => $items->where('status_text', 'success')->count(),
            'failed_count' => $items->where('status_text', 'failed')->count(),
            'resolved_count' => $items->where('status_text', 'resolved')->count(),
            'open_count' => $items->where('status_text', 'open')->count(),

            'critical_count' => $items->where('level_text', 'critical')->count(),
            'warning_count' => $items->where('level_text', 'warning')->count(),
            'info_count' => $items->where('level_text', 'info')->count(),
            'medium_count' => $items->where('level_text', 'medium')->count(),
            'high_count' => $items->where('level_text', 'high')->count(),
        ];

        return ApiResponse::paginated(
            $paginator,
            $pageItems,
            'Logs fetched successfully.'
        )->setData([
            'success' => true,
            'message' => 'Logs fetched successfully.',
            'data' => $pageItems,
            'meta' => [
                'stats' => $stats,
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'has_more_pages' => $paginator->hasMorePages(),
                ],
            ],
        ]);
    }
}