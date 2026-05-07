<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const SUPPORTED = ['pt_BR', 'en', 'es'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        App::setLocale($locale);
        session(['locale' => $locale]);

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        if ($request->has('locale') && in_array($request->query('locale'), self::SUPPORTED, true)) {
            return $request->query('locale');
        }

        if ($user = $request->user()) {
            if (in_array($user->locale ?? null, self::SUPPORTED, true)) {
                return $user->locale;
            }
        }

        if ($sessionLocale = session('locale')) {
            if (in_array($sessionLocale, self::SUPPORTED, true)) {
                return $sessionLocale;
            }
        }

        return config('app.locale');
    }
}
