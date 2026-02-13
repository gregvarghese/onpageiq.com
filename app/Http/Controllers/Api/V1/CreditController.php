<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Billing\CreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreditController extends Controller
{
    public function __construct(
        protected CreditService $creditService,
    ) {}

    /**
     * Get the credit balance for the organization.
     */
    public function balance(Request $request): JsonResponse
    {
        $organization = $request->user()->organization;

        return response()->json([
            'data' => [
                'balance' => $organization->credit_balance,
                'overdraft' => $organization->overdraft_balance,
                'subscription_tier' => $organization->subscription_tier,
            ],
        ]);
    }

    /**
     * Get credit transaction history.
     */
    public function transactions(Request $request): JsonResponse
    {
        $organization = $request->user()->organization;

        $transactions = $organization->creditTransactions()
            ->with('user:id,name')
            ->when($request->input('type'), function ($query) use ($request) {
                $query->where('type', $request->input('type'));
            })
            ->latest()
            ->paginate($request->input('per_page', 20));

        return response()->json($transactions);
    }
}
