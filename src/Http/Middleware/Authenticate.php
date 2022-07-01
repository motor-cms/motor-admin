<?php

namespace Motor\Admin\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * Class Authenticate
 */
class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param $request
     * @param  \Closure  $next
     * @param  null  $guard
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)
                ->guest()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 401);
            } else {
                return redirect()->guest('login');
            }
        }

        return $next($request);
    }
}
