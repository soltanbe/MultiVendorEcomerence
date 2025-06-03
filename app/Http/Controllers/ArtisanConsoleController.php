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
        'schedule:list',
        'cache:clear',
        'route:clear',
    ];

    public function list()
    {
        return response()->json([
            'commands' => [
                ['name' => 'migrate:fresh --seed', 'description' => '🧼 Reset DB and seed again'],
                ['name' => 'orders:random', 'description' => '📦 Create a random order for test'],
                ['name' => 'orders:process-pending', 'description' => '🔁 Process pending sub-orders by vendor and find the discounts'],
                ['name' => 'orders:notify-vendors', 'description' => '📨 Notify to vendors the suborders'],
                ['name' => 'schedule:list', 'description' => '📅 List scheduled commands'],
                ['name' => 'cache:clear', 'description' => '🧹 Clear Laravel cache'],
                ['name' => 'route:clear', 'description' => '🚧 Clear route cache'],
            ],
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

