<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ImpersonationController extends Controller
{
    /**
     * Start impersonating a user.
     */
    public function start(User $user, Request $request)
    {
        $admin = Auth::user();

        // Security: Only admins can impersonate, and they cannot impersonate other admins.
        if (! $admin->is_admin || ($user->is_admin && $user->id !== $admin->id)) {
            abort(403, 'Unauthorized impersonation attempt.');
        }

        Log::info("Admin ID {$admin->id} ({$admin->email}) started impersonating User ID {$user->id} ({$user->email})");

        $request->session()->put('impersonated_user_id', $user->id);
        $request->session()->put('original_admin_id', $admin->id);

        return redirect()->route('dashboard')->with('status', "Now impersonating {$user->name}");
    }

    /**
     * Stop impersonating and return to admin account.
     */
    public function stop(Request $request)
    {
        if (! $request->session()->has('impersonated_user_id')) {
            return redirect()->route('dashboard');
        }

        $adminId = $request->session()->get('original_admin_id');
        $userId = $request->session()->get('impersonated_user_id');

        Log::info("Admin ID {$adminId} stopped impersonating User ID {$userId}");

        $request->session()->forget('impersonated_user_id');
        $request->session()->forget('original_admin_id');

        return redirect()->route('admin.users')->with('status', 'Returned to Admin account.');
    }
}
