<?php

namespace App\Services\Security;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AbusePreventionService
{
    /**
     * Disposable email domains to block.
     *
     * @var array<string>
     */
    protected array $disposableDomains = [
        'tempmail.com',
        'throwaway.email',
        'guerrillamail.com',
        'mailinator.com',
        '10minutemail.com',
        'temp-mail.org',
        'fakeinbox.com',
        'sharklasers.com',
        'yopmail.com',
        'trashmail.com',
        'maildrop.cc',
        'getairmail.com',
        'dispostable.com',
        'mintemail.com',
        'tempail.com',
        'mailnesia.com',
    ];

    /**
     * Check if registration should be blocked.
     *
     * @return array{blocked: bool, reason: string|null}
     */
    public function checkRegistration(Request $request, string $email): array
    {
        // Check disposable email
        if ($this->isDisposableEmail($email)) {
            return [
                'blocked' => true,
                'reason' => 'Disposable email addresses are not allowed.',
            ];
        }

        // Check IP rate limiting for free accounts
        $ip = $request->ip();
        if ($this->hasExceededIpLimit($ip)) {
            return [
                'blocked' => true,
                'reason' => 'Too many accounts created from this IP address.',
            ];
        }

        // Check fingerprint if provided
        $fingerprint = $request->input('fingerprint');
        if ($fingerprint && $this->hasExceededFingerprintLimit($fingerprint)) {
            return [
                'blocked' => true,
                'reason' => 'Account limit reached for this device.',
            ];
        }

        return ['blocked' => false, 'reason' => null];
    }

    /**
     * Check if email is from a disposable domain.
     */
    public function isDisposableEmail(string $email): bool
    {
        $domain = strtolower(substr(strrchr($email, '@'), 1));

        // Check against static list
        if (in_array($domain, $this->disposableDomains)) {
            return true;
        }

        // Check cached extended list
        $extendedList = Cache::get('disposable_email_domains', []);
        if (in_array($domain, $extendedList)) {
            return true;
        }

        return false;
    }

    /**
     * Check if IP has exceeded free account limit.
     */
    public function hasExceededIpLimit(string $ip, int $limit = 3): bool
    {
        $count = User::where('registration_ip', $ip)
            ->whereHas('organization', function ($query) {
                $query->where('subscription_tier', 'free');
            })
            ->count();

        return $count >= $limit;
    }

    /**
     * Check if fingerprint has exceeded account limit.
     */
    public function hasExceededFingerprintLimit(string $fingerprint, int $limit = 2): bool
    {
        $hash = $this->hashFingerprint($fingerprint);

        $count = User::where('fingerprint_hash', $hash)
            ->whereHas('organization', function ($query) {
                $query->where('subscription_tier', 'free');
            })
            ->count();

        return $count >= $limit;
    }

    /**
     * Hash a browser fingerprint for storage.
     */
    public function hashFingerprint(string $fingerprint): string
    {
        return hash('sha256', $fingerprint.config('app.key'));
    }

    /**
     * Record registration details for a user.
     */
    public function recordRegistration(User $user, Request $request): void
    {
        $updates = [
            'registration_ip' => $request->ip(),
        ];

        if ($fingerprint = $request->input('fingerprint')) {
            $updates['fingerprint_hash'] = $this->hashFingerprint($fingerprint);
        }

        $user->update($updates);
    }

    /**
     * Get risk score for a user (0-100).
     */
    public function getRiskScore(User $user): int
    {
        $score = 0;

        // Same IP as other free accounts
        if ($user->registration_ip) {
            $sameIpCount = User::where('registration_ip', $user->registration_ip)
                ->where('id', '!=', $user->id)
                ->count();
            $score += min($sameIpCount * 15, 45);
        }

        // Same fingerprint as other accounts
        if ($user->fingerprint_hash) {
            $sameFingerprintCount = User::where('fingerprint_hash', $user->fingerprint_hash)
                ->where('id', '!=', $user->id)
                ->count();
            $score += min($sameFingerprintCount * 20, 40);
        }

        // Unverified email
        if (! $user->email_verified_at) {
            $score += 15;
        }

        return min($score, 100);
    }

    /**
     * Check if user appears suspicious.
     */
    public function isSuspicious(User $user): bool
    {
        return $this->getRiskScore($user) >= 50;
    }

    /**
     * Add a domain to the disposable list (cached).
     */
    public function addDisposableDomain(string $domain): void
    {
        $list = Cache::get('disposable_email_domains', []);
        $list[] = strtolower($domain);
        $list = array_unique($list);
        Cache::put('disposable_email_domains', $list, now()->addYear());
    }
}
