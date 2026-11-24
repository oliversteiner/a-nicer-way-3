<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Remote control: a few taps per second are normal, per pairing and address.
        RateLimiter::for('remote', fn (Request $request) => Limit::perMinute(240)->by($request->route('token').'|'.$request->ip()));
    }
}
