<?php

use App\Http\Controllers\CalculatorController;
use App\Http\Controllers\MortgageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [CalculatorController::class, 'show'])->name('home');

Route::get('/mortgage-simulation/{mortgage}', [CalculatorController::class, 'showMortgage'])
    ->middleware(['auth'])
    ->name('mortgage-simulation');

Route::get('dashboard', function () {
    $mortgages = auth()->user()->mortgages()
        ->orderBy('updated_at', 'desc')
        ->get();

    return Inertia::render('Dashboard', [
        'mortgages' => $mortgages,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::apiResource('mortgages', MortgageController::class);
});

require __DIR__.'/settings.php';
