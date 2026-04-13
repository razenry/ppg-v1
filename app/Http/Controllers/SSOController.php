<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SSOService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SSOController extends Controller
{
    public function __construct(protected SSOService $ssoService) {}

    /**
     * Handle SSO login via JWT token.
     */
    public function login(Request $request)
    {
        $token = $request->query('token');

        if (! $token) {
            return redirect()->route('login')->with('error', 'SSO token missing.');
        }

        $payload = $this->ssoService->validateToken($token);

        if (! $payload || empty($payload['user_id'])) {
            return redirect()->route('login')->with('error', 'Invalid or expired SSO token.');
        }

        $user = User::find($payload['user_id']);

        if (! $user) {
            return redirect()->route('login')->with('error', 'User not found.');
        }

        Auth::login($user);

        // If an admin generated this token, store their ID so they can return later.
        if (isset($payload['admin_id'])) {
            session()->put('sso_admin_id', $payload['admin_id']);
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Return back to the admin account after an SSO session.
     */
    public function returnToAdmin(Request $request)
    {
        $adminId = session('sso_admin_id');

        if (! $adminId) {
            abort(403, 'No original admin session found.');
        }

        $admin = User::find($adminId);

        if (! $admin || ! $admin->is_admin) {
            session()->forget('sso_admin_id');
            abort(403, 'Original admin account is invalid or no longer exists.');
        }

        Auth::login($admin);
        session()->forget('sso_admin_id');

        return redirect()->route('admin.users')->with('status', 'Returned to admin session.');
    }
}
