<?php

namespace App\Http\Controllers\Admin;

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class TerminalController extends Controller
{
    public function index()
    {
        return inertia('Admin/Terminal');
    }

    public function run(Request $request)
    {
        $command = $request->input('command');

        $allowed = [
            'cache:clear',
            'config:cache',
            'route:list',
            'migrate',
            'queue:restart',
            'sub-orders:notify-vendors',
        ];

        if (!in_array($command, $allowed)) {
            return response()->json([
                'output' => "❌ Command '{$command}' is not allowed.",
            ], 403);
        }

        Artisan::call($command);

        return response()->json([
            'output' => Artisan::output(),
        ]);
    }
}

