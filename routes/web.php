<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ArtisanConsoleController;

Route::middleware(['web'])->group(function () {
    Route::get('/login', [LoginController::class, 'show']);
    Route::post('/login', [LoginController::class, 'login']);
});
Route::middleware(['auth'])->group(function () {
    Route::get('/console', fn () => Inertia::render('ArtisanConsole'))->name('console');
    Route::get('/console/commands', [ArtisanConsoleController::class, 'list']);
    Route::post('/console/run', [ArtisanConsoleController::class, 'run']);
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});
