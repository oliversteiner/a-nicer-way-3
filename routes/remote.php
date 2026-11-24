<?php

use App\Http\Controllers\RemoteController;
use Illuminate\Support\Facades\Route;

Route::post('journeys/{journey:slug}/remote', [RemoteController::class, 'pair'])->middleware('throttle:20,1')->name('remote.pair');

Route::middleware('throttle:remote')->group(function () {
    Route::get('remote/{token}', [RemoteController::class, 'show'])->name('remote.show');
    Route::post('remote/{token}/command', [RemoteController::class, 'command'])->name('remote.command');
    Route::post('remote/{token}/state', [RemoteController::class, 'state'])->name('remote.state');
    Route::delete('remote/{token}', [RemoteController::class, 'destroy'])->name('remote.destroy');
});
