<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware for authority checking
 * Used when calling the API for administrators, etc.
 * Set "->middleware('authority:{function ID}');" in the API route settings.
 */
class MyAuthority
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request):(\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse) $next
     * @param $procId: function ID
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $procId)
    {
        //get login user
        $loginUser = \Auth::user();
        //authority check
        \ProcAuthority::checkAuthority($procId, $loginUser->id);
        return $next($request);
    }
}
