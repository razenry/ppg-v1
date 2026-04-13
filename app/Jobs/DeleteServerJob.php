<?php

namespace App\Jobs;

use App\Models\Node;
use App\Services\NodeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteServerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Node $node, public string $proxyId) {}

    /**
     * Execute the job.
     *
     * The Golang Gate Proxy Manager handles NGINX config + Cloudflare DNS deletion.
     * We just call DELETE /api/admin/gate/{proxy_id}.
     */
    public function handle(NodeService $nodeService): void
    {
        try {
            $nodeService->deleteProxy($this->node, $this->proxyId);
        } catch (\Exception $e) {
            Log::error("Error deleting external proxy {$this->proxyId}: ".$e->getMessage());
            // Don't rethrow — avoid infinite retry if resource is already gone or node is unreachable
        }
    }
}
