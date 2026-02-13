<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\Billing\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
    ) {}

    /**
     * List all projects for the organization.
     */
    public function index(Request $request): JsonResponse
    {
        $projects = Project::query()
            ->where('organization_id', $request->user()->organization_id)
            ->withCount('urls')
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json($projects);
    }

    /**
     * Create a new project.
     */
    public function store(Request $request): JsonResponse
    {
        $organization = $request->user()->organization;

        if (! $this->subscriptionService->canAddProject($organization)) {
            return response()->json([
                'message' => 'Project limit reached for your subscription tier.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'language' => ['nullable', 'string', 'max:10'],
            'check_config' => ['nullable', 'array'],
            'check_config.*' => ['boolean'],
        ]);

        $project = Project::create([
            'organization_id' => $organization->id,
            'created_by_user_id' => $request->user()->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'language' => $validated['language'] ?? 'en',
            'check_config' => $validated['check_config'] ?? $this->subscriptionService->getEnabledChecks($organization),
        ]);

        return response()->json([
            'data' => $project,
            'message' => 'Project created successfully.',
        ], 201);
    }

    /**
     * Get a specific project.
     */
    public function show(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request, $project);

        $project->loadCount('urls');

        return response()->json([
            'data' => $project,
        ]);
    }

    /**
     * Update a project.
     */
    public function update(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request, $project);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'language' => ['sometimes', 'string', 'max:10'],
            'check_config' => ['sometimes', 'array'],
            'check_config.*' => ['boolean'],
        ]);

        $project->update($validated);

        return response()->json([
            'data' => $project,
            'message' => 'Project updated successfully.',
        ]);
    }

    /**
     * Delete a project.
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request, $project);

        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully.',
        ]);
    }

    /**
     * Authorize that the user can access the project.
     */
    protected function authorizeProject(Request $request, Project $project): void
    {
        if ($project->organization_id !== $request->user()->organization_id) {
            abort(403, 'You do not have access to this project.');
        }
    }
}
