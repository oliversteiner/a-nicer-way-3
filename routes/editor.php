<?php

use App\Http\Controllers\Editor\BackdropController;
use App\Http\Controllers\Editor\BoardAssetController;
use App\Http\Controllers\Editor\BoardController;
use App\Http\Controllers\Editor\CharacterController;
use App\Http\Controllers\Editor\CharacterImageController;
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
    Route::post('boards/{board}/assets', [BoardAssetController::class, 'store'])->name('assets.store');
    Route::delete('assets/{asset}', [BoardAssetController::class, 'destroy'])->name('assets.destroy');
    Route::post('journeys/{journey:id}/characters', [CharacterController::class, 'store'])->name('characters.store');
    Route::put('characters/{character}', [CharacterController::class, 'update'])->name('characters.update');
    Route::delete('characters/{character}', [CharacterController::class, 'destroy'])->name('characters.destroy');
    Route::post('characters/{character}/images', [CharacterImageController::class, 'store'])->name('character-images.store');
    Route::delete('character-images/{image}', [CharacterImageController::class, 'destroy'])->name('character-images.destroy');
    Route::post('journeys/{journey:id}/backdrops', [BackdropController::class, 'store'])->name('backdrops.store');
    Route::put('backdrops/{backdrop}', [BackdropController::class, 'update'])->name('backdrops.update');
    Route::delete('backdrops/{backdrop}', [BackdropController::class, 'destroy'])->name('backdrops.destroy');
    Route::post('boards/{board}/move', [BoardController::class, 'move'])->name('boards.move');
});
