<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\JourneyController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('journeys/{journey:slug}', [JourneyController::class, 'show'])->name('journeys.show');

Route::get('assets/{asset}', [AssetController::class, 'show'])->name('assets.show');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/editor.php';
require __DIR__.'/remote.php';
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
