<?php

namespace App\Actions\Fortify;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
            // Check for disposable email domains (basic check)
            $emailDomain = substr(strrchr($input['email'], '@'), 1);
            $this->validateEmailDomain($emailDomain);

            // Check for IP-based abuse (same IP registering multiple free accounts)
            $this->checkIpAbuse();

            // Create organization for the user
            $organization = Organization::create([
                'name' => $input['name']."'s Organization",
                'subscription_tier' => 'free',
                'credit_balance' => 5,
            ]);

            // Create user
            return User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
                'organization_id' => $organization->id,
                'registration_ip' => request()->ip(),
            ]);
        });
    }

    /**
     * Validate that the email domain is not a known disposable email service.
     */
    protected function validateEmailDomain(string $domain): void
    {
        $disposableDomains = [
            'tempmail.com',
            'throwaway.email',
            'guerrillamail.com',
            'mailinator.com',
            '10minutemail.com',
            'temp-mail.org',
            'fakeinbox.com',
            'trashmail.com',
        ];

        if (in_array(strtolower($domain), $disposableDomains)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => ['Please use a valid email address. Disposable emails are not allowed.'],
            ]);
        }
    }

    /**
     * Check if this IP has already registered a free account.
     */
    protected function checkIpAbuse(): void
    {
        $ip = request()->ip();

        // Skip check for local development
        if (in_array($ip, ['127.0.0.1', '::1'])) {
            return;
        }

        $existingFreeUsers = User::where('registration_ip', $ip)
            ->whereHas('organization', fn ($q) => $q->where('subscription_tier', 'free'))
            ->count();

        if ($existingFreeUsers >= 1) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => ['A free account has already been registered from this location. Please upgrade to a paid plan or contact support.'],
            ]);
        }
    }
}
