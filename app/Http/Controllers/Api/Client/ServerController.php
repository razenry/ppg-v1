<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServerRequest;
use App\Models\Server;
use App\Services\ServerService;
use Illuminate\Http\JsonResponse;

class ServerController extends Controller
{
    public function __construct(
        protected ServerService $serverService
    ) {}

    /**
     * Display a listing of the user's servers.
     */
    public function index(): JsonResponse
    {
        $servers = auth()->user()->servers()->with(['node', 'subscription'])->get();

        return response()->json([
            'status' => 'success',
            'data' => $servers,
        ]);
    }

    /**
     * Provision a new reverse proxy server.
     */
    public function store(StoreServerRequest $request): JsonResponse
    {
        $this->authorize('create', Server::class);

        $server = $this->serverService->create(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Server provisioning started.',
            'data' => $server,
        ], 201);
    }

    /**
     * Display the specified server.
     */
    public function show(Server $server): JsonResponse
    {
        $this->authorize('view', $server);

        return response()->json([
            'status' => 'success',
            'data' => $server->load(['node', 'subscription']),
        ]);
    }

    /**
     * Delete the specified server.
     */
    public function destroy(Server $server): JsonResponse
    {
        $this->authorize('delete', $server);

        $this->serverService->delete($server);

        return response()->json([
            'status' => 'success',
            'message' => 'Server termination started.',
        ]);
    }
}
