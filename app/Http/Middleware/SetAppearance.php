<?php

namespace App\Http\Middleware;

use App\Enums\AppAppearance;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetAppearance
{
    public function handle(Request $request, Closure $next): Response
    {
        $appearance = $this->resolveAppearance($request);

        session(['appearance' => $appearance]);
        View::share('appearance', $appearance);

        return $next($request);
    }

    private function resolveAppearance(Request $request): string
    {
        $supported = AppAppearance::values();

        if ($request->has('appearance') && in_array($request->query('appearance'), $supported, true)) {
            return $request->query('appearance');
        }

        if ($user = $request->user()) {
            if (in_array($user->appearance ?? null, $supported, true)) {
                return $user->appearance;
            }
        }

        if ($sessionAppearance = session('appearance')) {
            if (in_array($sessionAppearance, $supported, true)) {
                return $sessionAppearance;
            }
        }

        return AppAppearance::System->value;
    }
}
