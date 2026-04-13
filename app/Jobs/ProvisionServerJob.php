<?php

namespace App\Jobs;

use App\Models\Server;
use App\Services\NodeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProvisionServerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of attempts before marking as failed.
     * Job will retry once if a connection timeout occurs.
     */
    public int $tries = 2;

    /**
     * Seconds to wait before retrying after a connection failure.
     */
    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(public Server $server) {}

    /**
     * Execute the job.
     *
     * Flow: Queue Job → POST /api/admin/gate → Golang handles NGINX + Cloudflare DNS
     * Golang responds with { "data": "kafka_{id}.raznar.net" } after ~2 minutes.
     * The browser never waits — it gets an instant "Pending" response.
     * The Livewire component polls every 5s and updates when status changes to "active".
     */
    public function handle(NodeService $nodeService): void
    {
        $context = [
            'server_id'  => $this->server->id,
            'identifier' => $this->server->identifier,
            'node'       => $this->server->node?->name ?? 'unknown',
            'src_ip'     => $this->server->src_ip,
            'src_port'   => $this->server->src_port,
            'dest_port'  => $this->server->dest_port,
            'attempt'    => $this->attempts(),
        ];

        Log::info('[Provision] Starting provisioning', $context);

        // Guard: node must exist
        if (! $this->server->node) {
            Log::error('[Provision] FAILED — node not found or not loaded', $context);
            $this->server->update(['status' => 'failed']);

            return;
        }

        Log::info('[Provision] Node resolved', [
            'node_id'  => $this->server->node->id,
            'node_url' => $this->server->node->api_url,
        ]);

        try {
            Log::info('[Provision] Calling NodeService::createProxy…', $context);

            // NodeService::createProxy returns the domain string on success,
            // or throws RuntimeException / ConnectionException on failure.
            $domain = $nodeService->createProxy(
                $this->server->node,
                $this->server->identifier,
                $this->server->src_ip,    // FiveM server IP (backend)
                $this->server->src_port,  // FiveM server port (backend)
                $this->server->dest_port  // NGINX proxy listening port
            );

            Log::info('[Provision] createProxy returned domain', ['domain' => $domain]);

            // Store gate ID and the exact domain returned by Golang
            $this->server->update([
                'proxy_id' => "kafka_{$this->server->identifier}",
                'domain'   => $domain,   // e.g. "kafka_gate.raznar.net"
                'status'   => 'active',
            ]);

            Log::info('[Provision] SUCCESS — server is now active', array_merge($context, [
                'domain' => $domain,
            ]));
        } catch (ConnectionException $e) {
            // Network/timeout error → retry once after 30s (stays as "pending" during retry)
            Log::warning('[Provision] CONNECTION TIMEOUT — will retry', array_merge($context, [
                'message' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'max'     => $this->tries,
            ]));

            // On final attempt, mark as failed
            if ($this->attempts() >= $this->tries) {
                Log::error('[Provision] FINAL ATTEMPT FAILED — marking server as failed', $context);
                $this->server->update(['status' => 'failed']);
            }

            throw $e; // Re-throw so the queue retries
        } catch (\RuntimeException $e) {
            // API returned an error (e.g. NGINX config conflict, auth failure)
            Log::error('[Provision] RUNTIME ERROR — Golang API rejected the request', array_merge($context, [
                'message' => $e->getMessage(),
            ]));
            $this->server->update(['status' => 'failed']);
            // Don't rethrow — no point retrying an API-level error
        } catch (\Exception $e) {
            Log::error('[Provision] UNEXPECTED EXCEPTION', array_merge($context, [
                'exception' => $e::class,
                'message'   => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
            ]));
            $this->server->update(['status' => 'failed']);
            throw $e;
        }
    }
}
