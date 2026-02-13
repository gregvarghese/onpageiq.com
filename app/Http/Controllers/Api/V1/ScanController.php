<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ScanUrlJob;
use App\Models\Scan;
use App\Models\Url;
use App\Services\Billing\CreditService;
use App\Services\Billing\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    public function __construct(
        protected CreditService $creditService,
        protected SubscriptionService $subscriptionService,
    ) {}

    /**
     * Trigger a new scan for a URL.
     */
    public function store(Request $request, Url $url): JsonResponse
    {
        $this->authorizeUrl($request, $url);

        $validated = $request->validate([
            'scan_type' => ['sometimes', 'in:quick,deep'],
        ]);

        $organization = $request->user()->organization;
        $scanType = $validated['scan_type'] ?? 'quick';
        $creditsRequired = $scanType === 'deep' ? 3 : 1;

        // Check credits
        if (! $this->creditService->hasCredits($organization, $creditsRequired)) {
            return response()->json([
                'message' => 'Insufficient credits. Required: '.$creditsRequired.', Available: '.$organization->credit_balance,
            ], 402);
        }

        // Create scan
        $scan = Scan::create([
            'url_id' => $url->id,
            'triggered_by_user_id' => $request->user()->id,
            'scan_type' => $scanType,
            'status' => 'pending',
            'credits_charged' => $creditsRequired,
        ]);

        // Get queue priority
        $queuePriority = $this->subscriptionService->getQueuePriority($organization);

        // Dispatch job
        ScanUrlJob::dispatch($scan)->onQueue($queuePriority);

        // Deduct credits
        $this->creditService->deductCredits(
            $organization,
            $creditsRequired,
            "Scan #{$scan->id} - {$url->url}",
            $request->user(),
            ['scan_id' => $scan->id]
        );

        return response()->json([
            'data' => $scan,
            'message' => 'Scan queued successfully.',
        ], 201);
    }

    /**
     * Get scan details.
     */
    public function show(Request $request, Scan $scan): JsonResponse
    {
        $this->authorizeScan($request, $scan);

        $scan->load(['url', 'result', 'triggeredBy:id,name']);

        return response()->json([
            'data' => [
                'id' => $scan->id,
                'url' => $scan->url->url,
                'scan_type' => $scan->scan_type,
                'status' => $scan->status,
                'credits_charged' => $scan->credits_charged,
                'started_at' => $scan->started_at,
                'completed_at' => $scan->completed_at,
                'triggered_by' => $scan->triggeredBy?->name,
                'scores' => $scan->result?->scores,
                'issue_count' => $scan->result?->issues ? count($scan->result->issues) : 0,
                'created_at' => $scan->created_at,
            ],
        ]);
    }

    /**
     * Get issues for a scan.
     */
    public function issues(Request $request, Scan $scan): JsonResponse
    {
        $this->authorizeScan($request, $scan);

        $scan->load('result');

        if (! $scan->result) {
            return response()->json([
                'data' => [],
                'message' => 'Scan results not yet available.',
            ]);
        }

        return response()->json([
            'data' => $scan->result->issues ?? [],
            'scores' => $scan->result->scores ?? [],
        ]);
    }

    protected function authorizeUrl(Request $request, Url $url): void
    {
        if ($url->project->organization_id !== $request->user()->organization_id) {
            abort(403, 'You do not have access to this URL.');
        }
    }

    protected function authorizeScan(Request $request, Scan $scan): void
    {
        if ($scan->url->project->organization_id !== $request->user()->organization_id) {
            abort(403, 'You do not have access to this scan.');
        }
    }
}
