<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNodeRequest;
use App\Http\Requests\UpdateNodeRequest;
use App\Models\Node;
use Illuminate\Http\JsonResponse;

class NodeController extends Controller
{
    /**
     * Display a listing of nodes.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Node::class);

        return response()->json([
            'status' => 'success',
            'data' => Node::all(),
        ]);
    }

    /**
     * Store a newly created node.
     */
    public function store(StoreNodeRequest $request): JsonResponse
    {
        $this->authorize('create', Node::class);

        $node = Node::create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Node created successfully.',
            'data' => $node,
        ], 201);
    }

    /**
     * Display the specified node.
     */
    public function show(Node $node): JsonResponse
    {
        $this->authorize('view', $node);

        return response()->json([
            'status' => 'success',
            'data' => $node,
        ]);
    }

    /**
     * Update the specified node.
     */
    public function update(UpdateNodeRequest $request, Node $node): JsonResponse
    {
        $this->authorize('update', $node);

        $data = $request->validated();
        if (empty($data['api_token'])) {
            unset($data['api_token']);
        }

        $node->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Node updated successfully.',
            'data' => $node,
        ]);
    }

    /**
     * Remove the specified node.
     */
    public function destroy(Node $node): JsonResponse
    {
        $this->authorize('delete', $node);

        if ($node->servers()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete node while servers are attached.',
            ], 422);
        }

        $node->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Node deleted successfully.',
        ]);
    }
}
