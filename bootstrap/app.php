<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\CheckInstallation::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\DemoModeMiddleware::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\UpdateUserActiveStatus::class,
        ]);
        $middleware->alias([
            'PlanModuleCheck' => \App\Http\Middleware\PlanModuleCheck::class,
            'api.json' => \App\Http\Middleware\ApiForceJson::class
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Authorization now lives in FormRequest authorize() / Gates, which throw
        // AuthorizationException. Laravel's prepareException() turns that into an
        // AccessDeniedHttpException before render callbacks run, so we match that.
        // Render it per client: the Inertia web UI keeps the app's existing "redirect
        // back with an error flash" denial UX (303 so PUT/PATCH/DELETE forms redirect
        // as GET), while genuine API/JSON clients get a real 403 so they can tell
        // "not allowed" apart from "nothing happened".
        $exceptions->render(function (AccessDeniedHttpException $e, $request) {
            if ($request->header('X-Inertia')) {
                return back(303)->with('error', __('You are not authorized to perform this action.'));
            }
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage() ?: 'This action is unauthorized.'], 403);
            }

            return null; // plain web falls through to the framework 403 page
        });
    })->create();
