<?php

use App\Exceptions\BusinessException;
use App\Exceptions\ResourceNotFound;
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

        // Se aplica antes de que Laravel lo convierta en NotFoundHttpException,
        // así un modelo inexistente se responde por el render de BusinessException
        $exceptions->map(ModelNotFoundException::class, fn (ModelNotFoundException $e) => new ResourceNotFound($e));

        // Solo llegan aquí las URLs que no existen
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => 'The requested URL was not found on the server. If you entered the URL manually please check your spelling and try again.',
            ], 404);
        });

        // Toda BusinessException (reglas de negocio y ResourceNotFound): cada una trae su código y ShouldntReport las saca del log
        $exceptions->render(fn (BusinessException $e) => response()->json([
            'message' => $e->getMessage(),
        ], $e->status()));
    })->create();
