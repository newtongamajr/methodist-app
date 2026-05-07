<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanManageFasting
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->can('fasting.calendar.manage'), 403);

        return $next($request);
    }
}
