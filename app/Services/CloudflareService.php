<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareService
{
    protected string $apiKey;

    protected string $email;

    protected string $zoneId;

    protected string $domain;

    public function __construct()
    {
        $config = Setting::get('cloudflare', []);
        $this->apiKey = $config['api_key'] ?? '';
        $this->email = $config['email'] ?? '';
        $this->zoneId = $config['zone_id'] ?? '';
        $this->domain = $config['domain'] ?? '';
    }

    /**
     * Create DNS records for a server.
     */
    public function createRecords(string $identifier, string $srcIp, int $srcPort): array
    {
        if (empty($this->apiKey) || empty($this->zoneId)) {
            Log::error('Cloudflare configuration missing');

            return ['success' => false, 'error' => 'Cloudflare configuration missing'];
        }

        // 1. Create A record: identifier.domain -> srcIp
        $aRecord = $this->createDnsRecord([
            'type' => 'A',
            'name' => "{$identifier}.{$this->domain}",
            'content' => $srcIp,
            'ttl' => 120,
            'proxied' => false,
        ]);

        if (! $aRecord['success']) {
            return $aRecord;
        }

        // 2. Create SRV record: _cfx._udp.identifier.domain -> srcPort (target: identifier.domain)
        $srvRecord = $this->createDnsRecord([
            'type' => 'SRV',
            'name' => "_cfx._udp.{$identifier}.{$this->domain}",
            'data' => [
                'service' => '_cfx',
                'proto' => '_udp',
                'name' => $identifier,
                'priority' => 0,
                'weight' => 5,
                'port' => $srcPort,
                'target' => "{$identifier}.{$this->domain}",
            ],
            'ttl' => 120,
        ]);

        return $srvRecord;
    }

    /**
     * Delete DNS records for a server.
     */
    public function deleteRecords(string $identifier): void
    {
        // In a real scenario, you'd store the record IDs in the DB or search for them
        // For this demo, we'll search by name and delete
        $this->deleteByName("{$identifier}.{$this->domain}");
        $this->deleteByName("_cfx._udp.{$identifier}.{$this->domain}");
    }

    protected function createDnsRecord(array $data): array
    {
        $response = Http::withHeaders([
            'X-Auth-Email' => $this->email,
            'X-Auth-Key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post("https://api.cloudflare.com/client/v4/zones/{$this->zoneId}/dns_records", $data);

        if ($response->successful()) {
            return ['success' => true, 'id' => $response->json('result.id')];
        }

        Log::error('Cloudflare DNS creation failed', ['response' => $response->json()]);

        return ['success' => false, 'error' => $response->json('errors.0.message')];
    }

    protected function deleteByName(string $name): void
    {
        $response = Http::withHeaders([
            'X-Auth-Email' => $this->email,
            'X-Auth-Key' => $this->apiKey,
        ])->get("https://api.cloudflare.com/client/v4/zones/{$this->zoneId}/dns_records", ['name' => $name]);

        if ($response->successful() && ! empty($response->json('result'))) {
            foreach ($response->json('result') as $record) {
                Http::withHeaders([
                    'X-Auth-Email' => $this->email,
                    'X-Auth-Key' => $this->apiKey,
                ])->delete("https://api.cloudflare.com/client/v4/zones/{$this->zoneId}/dns_records/{$record['id']}");
            }
        }
    }
}
