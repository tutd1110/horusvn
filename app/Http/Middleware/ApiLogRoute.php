<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use App\Models\LogRoute;
use Illuminate\Support\Facades\DB;

class ApiLogRoute
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
        $response = $next($request);

        $content = "";
        if (in_array($response->getStatusCode(), [404, 401, 403, 400, 422, 500])) {
            $content = json_encode($response->getContent());
        }

        DB::beginTransaction();

        LogRoute::create([
            'user_id' => Auth()->user()->id,
            'uri' => $request->getUri(),
            'request_body' => json_encode($request->all()),
            'response' => $content,
        ]);

        DB::commit();

        return $response;
    }
}
