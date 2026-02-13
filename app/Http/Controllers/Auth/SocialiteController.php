<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    /**
     * Redirect to Google OAuth.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback.
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        return $this->handleProviderCallback('google');
    }

    /**
     * Redirect to Microsoft OAuth.
     */
    public function redirectToMicrosoft(): RedirectResponse
    {
        return Socialite::driver('microsoft')->redirect();
    }

    /**
     * Handle Microsoft OAuth callback.
     */
    public function handleMicrosoftCallback(): RedirectResponse
    {
        return $this->handleProviderCallback('microsoft');
    }

    /**
     * Handle OAuth provider callback.
     */
    protected function handleProviderCallback(string $provider): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Unable to authenticate with '.$provider.'. Please try again.');
        }

        // Find existing user by provider ID or email
        $user = User::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if (! $user) {
            // Check if user exists with same email
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                // Link existing account with OAuth provider
                $user->update([
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                ]);
            } else {
                // Create new user
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'password' => Hash::make(Str::random(24)),
                    'email_verified_at' => now(),
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                ]);

                // TODO: Create organization for user and assign free tier credits
            }
        }

        Auth::login($user, remember: true);

        return redirect()->intended(config('fortify.home'));
    }
}
