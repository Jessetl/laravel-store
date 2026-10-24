<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by(Str::lower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by($request->ip()),
        ]);

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

        // Docs solo en local: fuera de local las rutas ni se registran (404, no 403).
        $local = $this->app->environment('local');
        Scramble::configure()
            ->routes(fn (Route $route) => Str::startsWith($route->uri, 'api/v1/') && ! Str::startsWith($route->uri, 'api/v1/swagger'))
            ->expose(
                ui: $local ? 'api/v1/swagger' : false,
                document: $local ? 'api/v1/swagger.json' : false,
            );
    }
}
