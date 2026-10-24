<?php

use App\Exceptions\DuplicateProductPrice;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => $e->getPrevious() instanceof ModelNotFoundException ? 'The requested resource was not found.'
                    : 'The requested URL was not found on the server. If you entered the URL manually please check your spelling and try again.',
            ], 404);
        });

        $exceptions->dontReport(DuplicateProductPrice::class);

        $exceptions->render(fn (DuplicateProductPrice $e) => response()->json([
            'message' => $e->getMessage(),
        ], 409));
    })->create();
