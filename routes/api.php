<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TerminalController;

Route::post('/terminal/run', [TerminalController::class, 'run']);
