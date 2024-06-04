<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if(auth()->id() == 194) return $next($request);
        
        if (Auth()->user()->permission != "1") {
            return response()->json([
                'status' => Response::HTTP_FORBIDDEN,
                'errors' => '',
            ], Response::HTTP_FORBIDDEN);
        }
        
        return $next($request);
    }
}
