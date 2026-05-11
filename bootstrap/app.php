<?php

use App\Http\Middleware\SetAppearance;
use App\Http\Middleware\SetLocale;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;
use Spatie\Permission\Exceptions\UnauthorizedException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
            SetAppearance::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // User-permission denials are expected behavior, not bugs. They
        // already render as 403 / redirects; logging them spams the sink.
        $exceptions->dontReport([
            AuthorizationException::class,
            UnauthorizedException::class,
        ]);

        // Drop duplicate report() calls within the same request cycle.
        $exceptions->dontReportDuplicates();

        // Tag every report with who/where/when so logs have context out of
        // the box without per-call hand-rolling.
        $exceptions->context(fn () => [
            'user_id' => optional(auth()->user())->id,
            'route' => optional(request()->route())->getName(),
            'locale' => app()->getLocale(),
        ]);

        // MediaLibrary rejection is user-driven (bad MIME, oversized, etc.)
        // — log at warning level instead of error and suppress the default
        // report so it doesn't trip alerting thresholds.
        $exceptions->report(function (FileCannotBeAdded $e) {
            Log::warning('MediaLibrary rejected an upload', [
                'message' => $e->getMessage(),
            ]);
            Context::add('handled_as', 'warning');

            return false;
        });
    })->create();
