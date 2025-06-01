<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Controller;

class ArtisanCommandController extends Controller
{
    // Only these commands can be run
    protected array $allowedCommands = [
        'migrate',
        'migrate:fresh',
        'config:cache',
        'queue:work',
        'queue:restart',
        'cache:clear',
        'route:clear',
        'schedule:run',
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

            return response()->json([
                'success' => true,
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
