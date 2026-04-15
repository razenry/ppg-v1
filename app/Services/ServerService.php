<?php

namespace App\Services;

use App\Jobs\DeleteServerJob;
use App\Jobs\ProvisionServerJob;
use App\Models\Server;
use App\Models\Setting;
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
            ->first();

        if (! $subscription || ! $subscription->isActive()) {
            throw ValidationException::withMessages(['subscription' => 'No active or valid subscription found.']);
        }

        // Check limits
        $currentCount = $user->servers()->where('subscription_id', $subscription->id)->count();
        if ($currentCount >= $subscription->max_server) {
            throw ValidationException::withMessages(['subscription' => 'Server limit reached for this subscription.']);
        }

        return DB::transaction(function () use ($user, $data, $subscription) {
            // Generate random proxy port (now mandatory to be random as per requirement)
            $destPort = $this->generateRandomPort($data['node_id']);

            $server = Server::create([
                'user_id' => $user->id,
                'label' => $data['label'],
                'identifier' => strtolower($data['identifier']),
                'src_ip' => $data['src_ip'],
                'src_port' => $data['src_port'],   // backend port (e.g. 30120)
                'dest_port' => $destPort,          // NGINX proxy listening port (random)
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

    /**
     * Suspend all servers for a subscription (trigger proxy deletion but keep DB records).
     */
    public function suspend(Subscription $subscription): void
    {
        foreach ($subscription->servers as $server) {
            $proxyId = $server->proxy_id ?? "kafka_{$server->identifier}";
            if ($server->node_id) {
                DeleteServerJob::dispatch($server->node, $proxyId);
            }
            $server->update(['status' => 'suspended']);
        }
    }

    /**
     * Generate a random port within a configurable range that is not used on the node.
     */
    public function generateRandomPort(int $nodeId): int
    {
        $range = Setting::get('port_range', [
            'min' => 20000,
            'max' => 60000,
        ]);

        $usedPorts = Server::where('node_id', $nodeId)
            ->pluck('dest_port')
            ->toArray();

        do {
            $port = rand($range['min'], $range['max']);
        } while (in_array($port, $usedPorts));

        return $port;
    }
}
