<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ArtisanConsoleController extends Controller
{
    protected array $allowedCommands = [
        'migrate:fresh --seed',
        'orders:random',
        'orders:process-pending',
        'orders:notify-vendors',
        'cache:clear',
        'route:clear',
    ];

    public function list()
    {
        return response()->json([
            'commands' => $this->allowedCommands,
        ]);
    }

    public function run(Request $request)
    {
        $command = $request->input('command');

        if (!in_array($command, $this->allowedCommands)) {
            return response()->json(['success' => false, 'error' => 'Command not allowed.'], 403);
        }

        try {
            Artisan::call($command);
            $output = Artisan::output();

            return response()->json(['success' => true, 'output' => $output]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}

