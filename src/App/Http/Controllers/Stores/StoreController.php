<?php

namespace App\Http\Controllers\Stores;

use App\Http\Controllers\Controller;

class StoreController extends Controller
{
    public function __invoke()
    {
        return view('pages.stores.index');
    }

    public function details()
    {
        return view('pages.stores.details');
    }
}
