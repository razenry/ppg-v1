<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceTwoFactor
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Ignored routes to prevent redirect loops
        if ($request->routeIs('profile.*') || $request->routeIs('logout')) {
            return $next($request);
        }

        $enforcement = Setting::get('2fa_enforcement', 'none'); // 'none', 'admin', 'all'

        $requires2FA = false;

        if ($enforcement === 'all') {
            $requires2FA = true;
        } elseif ($enforcement === 'admin' && $user->isAdmin()) {
            $requires2FA = true;
        }

        if ($requires2FA && empty($user->two_factor_secret)) {
            // Flash a message to instruct them to enable 2FA
            session()->flash('warning', 'You must enable Two-Factor Authentication to access this area.');

            return redirect()->route('profile.edit');
        }

        return $next($request);
    }
}
