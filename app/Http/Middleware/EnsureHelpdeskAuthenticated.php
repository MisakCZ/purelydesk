<?php

namespace App\Http\Middleware;

use App\Support\HelpdeskAuth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHelpdeskAuthenticated
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() !== null) {
            return $next($request);
        }

        if (
            config('helpdesk.auth.allow_temporary_user_fallback', false)
            && app(HelpdeskAuth::class)->user() !== null
        ) {
            return $next($request);
        }

        return redirect()->guest(route('login'));
    }
}
