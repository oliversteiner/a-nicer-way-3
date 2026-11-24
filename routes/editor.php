<?php

use App\Http\Controllers\Editor\BoardController;
use App\Http\Controllers\Editor\JourneyController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('editor')->name('editor.')->group(function () {
    Route::get('journeys', [JourneyController::class, 'index'])->name('journeys.index');
    Route::get('journeys/create', [JourneyController::class, 'create'])->name('journeys.create');
    Route::post('journeys', [JourneyController::class, 'store'])->name('journeys.store');
    Route::get('journeys/{journey:id}', [JourneyController::class, 'edit'])->name('journeys.edit');
    Route::put('journeys/{journey:id}', [JourneyController::class, 'update'])->name('journeys.update');
    Route::delete('journeys/{journey:id}', [JourneyController::class, 'destroy'])->name('journeys.destroy');

    Route::post('journeys/{journey:id}/boards', [BoardController::class, 'store'])->name('boards.store');
    Route::put('boards/{board}', [BoardController::class, 'update'])->name('boards.update');
    Route::delete('boards/{board}', [BoardController::class, 'destroy'])->name('boards.destroy');
    Route::post('boards/{board}/move', [BoardController::class, 'move'])->name('boards.move');
});
