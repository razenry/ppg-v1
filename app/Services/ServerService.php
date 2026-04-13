<?php

namespace App\Services;

use App\Jobs\DeleteServerJob;
use App\Jobs\ProvisionServerJob;
use App\Models\Server;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServerService
{
    /**
     * Create a new server for a user.
     */
    public function create(User $user, array $data): Server
    {
        $subscription = $user->subscriptions()
            ->where('id', $data['subscription_id'])
            ->where('status', 'active')
            ->first();

        if (! $subscription) {
            throw ValidationException::withMessages(['subscription' => 'No active subscription found.']);
        }

        // Check limits
        $currentCount = $user->servers()->where('subscription_id', $subscription->id)->count();
        if ($currentCount >= $subscription->max_server) {
            throw ValidationException::withMessages(['subscription' => 'Server limit reached for this subscription.']);
        }

        return DB::transaction(function () use ($user, $data, $subscription) {
            $server = Server::create([
                'user_id' => $user->id,
                'label' => $data['label'],
                'identifier' => strtolower($data['identifier']),
                'src_ip' => $data['src_ip'],
                'src_port' => $data['src_port'],
                'dest_port' => $data['dest_port'],  // NGINX proxy listening port
                'node_id' => $data['node_id'],
                'subscription_id' => $subscription->id,
                'status' => 'pending',
            ]);

            ProvisionServerJob::dispatch($server);

            return $server;
        });
    }

    /**
     * Delete a server.
     */
    public function delete(Server $server): void
    {
        // Pass Node and proxy_id directly because we delete the Server model immediately.
        // If we passed the Server model, the queued job would fail to unserialize it.
        $proxyId = $server->proxy_id ?? "kafka_{$server->identifier}";

        if ($server->node_id) {
            DeleteServerJob::dispatch($server->node, $proxyId);
        }

        $server->delete();
    }

    /**
     * Redeploy a server.
     */
    public function redeploy(Server $server): void
    {
        $server->update(['status' => 'pending']);
        ProvisionServerJob::dispatch($server);
    }
}
