<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Credit;


class CustomersInfoController extends Controller
{

    public function __invoke(Request $request)
    {
        $q  = trim((string) $request->get('q', ''));
        $id = $request->get('id'); // opsiyonel: satır tıklanınca url'e id yazmak istersen kullanırız

        $credit_users_info = Credit::query()
            ->when($q !== '', function ($query) use ($q) {
                $like = '%' . $q . '%';
                $query->where(function ($qq) use ($like) {
                    $qq->where('name', 'ilike', $like)
                        ->orWhere('phone', 'ilike', $like)
                        ->orWhere('passport', 'ilike', $like)
                        ->orWhere('contract', 'ilike', $like)
                        ->orWhere('clientref', 'ilike', $like);
                });
            })
            ->orderByDesc('rv_bigint')
            ->paginate(16)
            ->appends($request->query());

        // ✅ Sayfadaki kayıtları collection yap
        $pageItems = collect($credit_users_info->items());

        // ✅ Sağ panel seçili kayıt
        $selected = null;

        if (!empty($id)) {
            // önce bu sayfanın içinden bul
            $selected = $pageItems->firstWhere('logicalref', (int) $id);

            // yoksa DB’den çek (id başka sayfadaysa)
            if (!$selected) {
                $selected = Credit::query()->where('logicalref', (int) $id)->first();
            }
        }

        // id yoksa ilk kaydı seç
        if (!$selected) {
            $selected = $pageItems->first();
        }

        return view('pages.customers.info', compact('credit_users_info', 'selected', 'q'));
    }
}
