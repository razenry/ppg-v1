<?php

namespace App\Services;

use App\Models\Node;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NodeService
{
    /**
     * Test the connection to a node and return latency.
     *
     * @return array{online: bool, latency_ms: int|null}
     */
    public function testConnection(Node $node): array
    {
        try {
            $start    = hrtime(true);
            $response = Http::timeout(5)->get("{$node->api_url}/api/health");
            $latency  = (int) round((hrtime(true) - $start) / 1_000_000); // ns → ms

            return [
                'online'     => $response->successful(),
                'latency_ms' => $response->successful() ? $latency : null,
            ];
        } catch (\Exception $e) {
            Log::warning("[NodeService] testConnection failed for node {$node->name}: {$e->getMessage()}");

            return ['online' => false, 'latency_ms' => null];
        }
    }

    /**
     * Create a proxy gate on a node.
     *
     * POST /api/admin/gate
     * Request:
     *   { "id": "kafka_{identifier}", "backend": "{src_ip}:{src_port}", "port": {dest_port} }
     *   ─ id      : gate identifier
     *   ─ backend : FiveM server address (ip:port) — traffic is proxied HERE
     *   ─ port    : NGINX proxy listen port — players connect to this
     * Response: { "data": "kafka_{identifier}.raznar.net" }
     *
     * @throws \RuntimeException        if the API returns a non-2xx response
     * @throws \Illuminate\Http\Client\ConnectionException if the connection times out
     */
    public function createProxy(Node $node, string $identifier, string $backend, int $backendPort, int $proxyPort): string
    {
        $gateId  = "kafka_{$identifier}";
        $payload = [
            'id'      => $gateId,
            'backend' => "{$backend}:{$backendPort}", // FiveM server: ip:port
            'port'    => $proxyPort,                  // NGINX listens on this port
        ];

        Log::info('[NodeService] createProxy — sending request', [
            'node'    => $node->name,
            'url'     => "{$node->api_url}/api/admin/gate",
            'payload' => $payload,
        ]);

        $response = Http::withToken($node->api_token)
            ->asJson()
            ->timeout(300) // Golang provisioning (NGINX + Cloudflare DNS) can take up to 5 minutes
            ->post("{$node->api_url}/api/admin/gate", $payload);

        Log::info('[NodeService] createProxy — response received', [
            'http_status' => $response->status(),
            'body'        => $response->body(),
        ]);

        if (! $response->successful()) {
            $error = $response->json('error') ?? $response->json('message') ?? $response->body();

            Log::error('[NodeService] createProxy — FAILED (non-2xx response)', [
                'node'        => $node->name,
                'gate_id'     => $gateId,
                'http_status' => $response->status(),
                'error'       => $error,
            ]);

            throw new \RuntimeException("Failed to create gate on proxy node {$node->name}: {$error}");
        }

        $domain = $response->json('data');

        Log::info('[NodeService] createProxy — SUCCESS', [
            'gate_id' => $gateId,
            'domain'  => $domain,
        ]);

        return $domain; // e.g. "kafka_myrpserver.raznar.net"
    }

    /**
     * Delete a proxy gate on a node.
     *
     * DELETE /api/admin/gate/{id}
     * 204 No Content = success
     * 404 Not Found  = already gone (idempotent, not an error)
     *
     * @throws \RuntimeException if the API returns an unexpected error response
     */
    public function deleteProxy(Node $node, string $proxyId): void
    {
        Log::info('[NodeService] deleteProxy — sending request', [
            'node'     => $node->name,
            'proxy_id' => $proxyId,
            'url'      => "{$node->api_url}/api/admin/gate/{$proxyId}",
        ]);

        $response = Http::withToken($node->api_token)
            ->timeout(30)
            ->delete("{$node->api_url}/api/admin/gate/{$proxyId}");

        Log::info('[NodeService] deleteProxy — response received', [
            'http_status' => $response->status(),
            'body'        => $response->body(),
        ]);

        // 404 = gate already gone (idempotent success — same as reference)
        if ($response->status() === 404) {
            Log::info("[NodeService] deleteProxy — gate {$proxyId} not found on {$node->name} (already removed)");

            return;
        }

        // 204 No Content or any 2xx = success
        if ($response->successful()) {
            Log::info("[NodeService] deleteProxy — SUCCESS, gate {$proxyId} removed from {$node->name}");

            return;
        }

        // Any other status = real error → throw
        $error = $response->json('error') ?? $response->json('message') ?? $response->body();

        Log::error('[NodeService] deleteProxy — FAILED', [
            'node'        => $node->name,
            'proxy_id'    => $proxyId,
            'http_status' => $response->status(),
            'error'       => $error,
        ]);

        throw new \RuntimeException("Failed to delete gate {$proxyId} on proxy node {$node->name}: {$error}");
    }
}
