<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Impersonate
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has('impersonated_user_id')) {
            $userId = $request->session()->get('impersonated_user_id');
            Auth::onceUsingId($userId);

            if ($userId == $request->session()->get('original_admin_id')) {
                Auth::user()->is_admin = false;
            }
        }

        return $next($request);
    }
}
