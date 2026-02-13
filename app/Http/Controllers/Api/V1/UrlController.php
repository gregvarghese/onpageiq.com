<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Url;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class UrlController extends Controller
{
    /**
     * List all URLs for a project.
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request, $project);

        $urls = $project->urls()
            ->withCount('scans')
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json($urls);
    }

    /**
     * Add a URL to a project.
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request, $project);

        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
        ]);

        // Check if URL already exists in project
        $existingUrl = $project->urls()->where('url', $validated['url'])->first();
        if ($existingUrl) {
            return response()->json([
                'message' => 'URL already exists in this project.',
                'data' => $existingUrl,
            ], 409);
        }

        // Verify URL is reachable
        try {
            $response = Http::timeout(10)->head($validated['url']);
            $contentType = $response->header('Content-Type');

            if (! str_contains($contentType ?? '', 'text/html')) {
                return response()->json([
                    'message' => 'URL must return HTML content.',
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'URL is not reachable: '.$e->getMessage(),
            ], 422);
        }

        $url = $project->urls()->create([
            'url' => $validated['url'],
            'status' => 'pending',
        ]);

        return response()->json([
            'data' => $url,
            'message' => 'URL added successfully.',
        ], 201);
    }

    /**
     * Get a specific URL.
     */
    public function show(Request $request, Url $url): JsonResponse
    {
        $this->authorizeUrl($request, $url);

        $url->load('project')->loadCount('scans');

        return response()->json([
            'data' => $url,
        ]);
    }

    /**
     * Delete a URL.
     */
    public function destroy(Request $request, Url $url): JsonResponse
    {
        $this->authorizeUrl($request, $url);

        $url->delete();

        return response()->json([
            'message' => 'URL deleted successfully.',
        ]);
    }

    protected function authorizeProject(Request $request, Project $project): void
    {
        if ($project->organization_id !== $request->user()->organization_id) {
            abort(403, 'You do not have access to this project.');
        }
    }

    protected function authorizeUrl(Request $request, Url $url): void
    {
        if ($url->project->organization_id !== $request->user()->organization_id) {
            abort(403, 'You do not have access to this URL.');
        }
    }
}
