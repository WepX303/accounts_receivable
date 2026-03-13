<?php

namespace App\Http\Controllers\Api\V1\Customers;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreditDetailResource;
use App\Http\Resources\CreditListResource;
use App\Models\Credit;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class ApiCustomerController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $q = trim((string) $request->input('q', ''));
        $perPage = (int) $request->input('per_page', 20);

        $query = Credit::query();

        if ($q !== '') {
            $like = '%' . $q . '%';

            $query->where(function ($sub) use ($like) {
                $sub->where('name', 'ilike', $like)
                    ->orWhere('phone', 'ilike', $like)
                    ->orWhere('passport', 'ilike', $like)
                    ->orWhere('contract', 'ilike', $like)
                    ->orWhere('clientref', 'ilike', $like)
                    ->orWhere('assurance', 'ilike', $like);
            });
        }

        $paginator = $query
            ->orderByDesc('rv_bigint')
            ->paginate($perPage)
            ->appends($request->query());

        return ApiResponse::paginated(
            $paginator,
            CreditListResource::collection($paginator->getCollection())->resolve(),
            'Customers fetched successfully.'
        );
    }

    public function search(Request $request)
    {
        $request->validate([
            'q' => ['required', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $q = trim((string) $request->input('q'));
        $limit = (int) $request->input('limit', 20);

        $like = '%' . $q . '%';

        $items = Credit::query()
            ->where(function ($sub) use ($like) {
                $sub->where('name', 'ilike', $like)
                    ->orWhere('phone', 'ilike', $like)
                    ->orWhere('passport', 'ilike', $like)
                    ->orWhere('contract', 'ilike', $like)
                    ->orWhere('clientref', 'ilike', $like)
                    ->orWhere('assurance', 'ilike', $like);
            })
            ->orderByDesc('rv_bigint')
            ->limit($limit)
            ->get();

        return ApiResponse::success(
            CreditListResource::collection($items)->resolve(),
            'Customer search completed successfully.'
        );
    }

    public function show(Credit $credit)
    {
        $credit->load([
            'paidUpdatedByUser:id,firstname,lastname',
            'amountUpdatedByUser:id,firstname,lastname',
        ]);

        return ApiResponse::success(
            new CreditDetailResource($credit),
            'Customer fetched successfully.'
        );
    }
}