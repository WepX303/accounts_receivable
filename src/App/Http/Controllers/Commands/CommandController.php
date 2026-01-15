<?php

namespace App\Http\Controllers\Commands;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Controller;

class CommandController extends Controller
{
    protected $commands = [
        'cache:clear'      => 'Cache Temizle',
        'config:clear'     => 'Config Cache Temizle',
        'view:clear'       => 'View Cache Temizle',
        'route:clear'       => 'Route Cache Temizle',
        // İhtiyacına göre daha fazla komut ekleyebilirsin
    ];

    public function index()
    {
        return view('commands.index', ['commands' => $this->commands]);
    }

    public function run(Request $request)
    {
        $request->validate([
            'command' => 'required|in:' . implode(',', array_keys($this->commands))
        ]);

        try {
            Artisan::call($request->command);
            $output = Artisan::output();
            $status = 'success';
        } catch (\Exception $e) {
            $output = $e->getMessage();
            $status = 'error';
        }

        return back()->with([
            'output' => $output,
            'command' => $this->commands[$request->command],
            'status' => $status,
        ]);
    }
}
