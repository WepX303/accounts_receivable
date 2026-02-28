<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\Credit;
use Illuminate\Http\Request;

class CustomersInfoController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:100',
            'id' => 'nullable|integer|min:1',
        ], [
            'q.string' => __('validations/validations.customers_info.q_string'),
            'q.max' => __('validations/validations.customers_info.q_max'),
            'id.integer' => __('validations/validations.customers_info.id_integer'),
            'id.min' => __('validations/validations.customers_info.id_min'),
        ]);

        $q = trim((string) $request->get('q', ''));
        // $id = $request->get('id');
        $id = $request->integer('id'); // null or int

        $credit_users_info = Credit::query()
            ->when($q !== '', function ($query) use ($q) {
                $like = '%' . $q . '%';
                $query->where(function ($qq) use ($like) {
                    $qq->where('name', 'ilike', $like)
                        ->orWhere('phone', 'ilike', $like)
                        ->orWhere('passport', 'ilike', $like)
                        ->orWhere('contract', 'ilike', $like)
                        ->orWhere('clientref', 'ilike', $like)
                        ->orWhere('assurance', 'ilike', $like);
                });
            })
            ->orderByDesc('rv_bigint')
            ->paginate(16)
            ->appends($request->query());

        // Create a collection of the records on the page
        $pageItems = collect($credit_users_info->items());

        // Selected record in the right panel
        $selected = null;

        if (! empty($id)) {
            // first find it within this page
            $selected = $pageItems->firstWhere('logicalref', (int) $id);
            // otherwise fetch from the database (if the id is on another page)
            if (! $selected) {
                $selected = Credit::query()->where('logicalref', (int) $id)->first();
            }
        }
        // If there is no ID, select the first record
        if (! $selected) {
            $selected = $pageItems->first();
        }

        return view('pages.customers.info', compact('credit_users_info', 'selected', 'q'));
    }
}
