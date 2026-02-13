<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Create a new API token.
     */
    public function createToken(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'device_name' => ['required', 'string', 'max:255'],
            'abilities' => ['sometimes', 'array'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $abilities = $request->abilities ?? ['*'];
        $token = $user->createToken($request->device_name, $abilities);

        return response()->json([
            'token' => $token->plainTextToken,
            'expires_at' => null,
        ], 201);
    }

    /**
     * Revoke the current token.
     */
    public function revokeToken(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Token revoked successfully.',
        ]);
    }

    /**
     * Get the authenticated user.
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user()->load('organization');

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'organization' => [
                    'id' => $user->organization->id,
                    'name' => $user->organization->name,
                    'subscription_tier' => $user->organization->subscription_tier,
                    'credit_balance' => $user->organization->credit_balance,
                ],
            ],
        ]);
    }
}
