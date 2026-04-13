<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Log;

class SSOService
{
    protected string $secretKey;

    protected int $ttl; // in seconds

    public function __construct()
    {
        $this->secretKey = Setting::get('sso_secret', config('app.key'));
        $this->ttl = (int) Setting::get('sso_ttl', 120);
    }

    /**
     * Generate an SSO JWT token for a user.
     * Optionally include the admin ID who generated it to allow returning to Admin.
     */
    public function generateToken(User $user, ?int $adminId = null): string
    {
        $payload = [
            'iss' => config('app.url'),
            'sub' => $user->id,
            'exp' => time() + $this->ttl,
            'iat' => time(),
        ];

        if ($adminId) {
            $payload['admin_id'] = $adminId;
        }

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    /**
     * Validate an SSO JWT token and return the payload details.
     * 
     * @return array{user_id: int, admin_id?: int}|null
     */
    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));

            $result = ['user_id' => (int) $decoded->sub];
            if (isset($decoded->admin_id)) {
                $result['admin_id'] = (int) $decoded->admin_id;
            }

            return $result;
        } catch (\Exception $e) {
            Log::warning('Invalid SSO token used', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
