<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WebhookEndpoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WebhookController extends Controller
{
    /**
     * List all webhook endpoints for the organization.
     */
    public function index(Request $request): JsonResponse
    {
        $endpoints = WebhookEndpoint::query()
            ->where('organization_id', $request->user()->organization_id)
            ->withCount('deliveries')
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json($endpoints);
    }

    /**
     * Create a new webhook endpoint.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string', Rule::in(WebhookEndpoint::ALL_EVENTS)],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $endpoint = WebhookEndpoint::create([
            'organization_id' => $request->user()->organization_id,
            'url' => $validated['url'],
            'events' => $validated['events'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'data' => $endpoint,
            'message' => 'Webhook endpoint created successfully.',
        ], 201);
    }

    /**
     * Get a specific webhook endpoint.
     */
    public function show(Request $request, WebhookEndpoint $webhook): JsonResponse
    {
        $this->authorizeEndpoint($request, $webhook);

        $webhook->loadCount('deliveries');

        return response()->json([
            'data' => $webhook,
        ]);
    }

    /**
     * Update a webhook endpoint.
     */
    public function update(Request $request, WebhookEndpoint $webhook): JsonResponse
    {
        $this->authorizeEndpoint($request, $webhook);

        $validated = $request->validate([
            'url' => ['sometimes', 'url', 'max:2048'],
            'events' => ['sometimes', 'array', 'min:1'],
            'events.*' => ['string', Rule::in(WebhookEndpoint::ALL_EVENTS)],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $webhook->update($validated);

        return response()->json([
            'data' => $webhook,
            'message' => 'Webhook endpoint updated successfully.',
        ]);
    }

    /**
     * Delete a webhook endpoint.
     */
    public function destroy(Request $request, WebhookEndpoint $webhook): JsonResponse
    {
        $this->authorizeEndpoint($request, $webhook);

        $webhook->delete();

        return response()->json([
            'message' => 'Webhook endpoint deleted successfully.',
        ]);
    }

    protected function authorizeEndpoint(Request $request, WebhookEndpoint $webhook): void
    {
        if ($webhook->organization_id !== $request->user()->organization_id) {
            abort(403, 'You do not have access to this webhook endpoint.');
        }
    }
}
