<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\TerminalController;


use App\Http\Controllers\ArtisanConsoleController;

Route::get('/console', fn () => Inertia::render('ArtisanConsole'))->name('console');

Route::get('/console/commands', [ArtisanConsoleController::class, 'list']);
Route::post('/console/run', [ArtisanConsoleController::class, 'run']);
