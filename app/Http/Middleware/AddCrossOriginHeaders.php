<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddCrossOriginHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        if (method_exists($response, 'header')) {
            $response->header('Cross-Origin-Opener-Policy', 'same-origin');
            $response->header('Cross-Origin-Embedder-Policy', 'require-corp');
        }

        return $response;
    }
}
