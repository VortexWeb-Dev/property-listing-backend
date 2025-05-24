<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register CORS middleware globally
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthorizationException $e) {
            return response()->json([
                'message' => 'This action is unauthorized.',
            ], 403);
        });
        
        $exceptions->render(function (AccessDeniedHttpException $e) {
            return response()->json([
                'message' => 'This action is unauthorized.',
            ], 403);
        });
        
        $exceptions->render(function (NotFoundHttpException $e) {
            return response()->json([
                'message' => 'Data Not found.',
            ], 404);
        });
        
    })->create();