<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\TerminalController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/', function () {
    return Inertia::render('Home', [
        'name' => 'Sultan'
    ]);
});
Route::get('/terminal', [TerminalController::class, 'index'])->name('admin.terminal');
Route::post('/terminal/run', [TerminalController::class, 'run'])->name('admin.terminal.run');
