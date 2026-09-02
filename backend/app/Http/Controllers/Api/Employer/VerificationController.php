<?php

namespace App\Http\Controllers\Api\Employer;

use App\Http\Controllers\Controller;
use App\Models\Verification;
use App\Services\Verification\Checkers\WorkEmailDomainChecker;
use App\Services\Verification\VerificationService;
use App\Services\Verification\VerificationUnavailable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function __construct(
        private VerificationService $verifications,
        private WorkEmailDomainChecker $domain,
    ) {}

    /**
     * Everything the verification portal needs to render: current state, what
     * this deployment can check, and the DNS record to publish.
     */
    public function show(Request $request): JsonResponse
    {
        $employer = $request->user()->employerProfile;
        $employer->load('verifications');

        return response()->json([
            'verification' => [
                'is_verified'     => (bool) $employer->is_verified,
                'verified_at'     => $employer->verified_at,
                'badges'          => $employer->activeBadges(),
                'available_types' => $this->verifications->availableTypes($employer),
                'records'         => $employer->verifications->map(fn (Verification $v) => [
                    'type'             => $v->type,
                    'status'           => $v->status,
                    'provider'         => $v->provider,
                    'metadata'         => $v->metadata,
                    'verified_at'      => $v->verified_at,
                    'expires_at'       => $v->expires_at,
                    'rejection_reason' => $v->rejection_reason,
                ])->values(),
                'dns_instructions' => [
                    'record' => 'TXT',
                    'host'   => $employer->work_email_domain ?: $this->emailDomain($request),
                    'value'  => $this->domain->tokenFor($employer),
                ],
            ],
        ]);
    }

    /**
     * Run a verification check for this employer.
     */
    public function store(Request $request): JsonResponse
    {
        $employer = $request->user()->employerProfile;

        $validated = $request->validate([
            'type'                         => ['required', 'in:'.implode(',', Verification::EMPLOYER_TYPES)],
            'business_registration_number' => ['nullable', 'string', 'max:100'],
        ]);

        if (filled($validated['business_registration_number'] ?? null)) {
            $employer->update(['business_registration_number' => $validated['business_registration_number']]);
        }

        try {
            $verification = $this->verifications->verify($employer, $validated['type'], $validated);
        } catch (VerificationUnavailable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code'    => 'VERIFICATION_PROVIDER_UNAVAILABLE',
            ], 503);
        }

        // Cache the proven domain so listings can show it without a join.
        if ($verification->isActive() && $verification->type === 'work_email_domain') {
            $employer->update(['work_email_domain' => $verification->metadata['domain'] ?? null]);
        }

        return response()->json([
            'verification' => $verification,
            'is_verified'  => (bool) $employer->fresh()->is_verified,
        ], $verification->isActive() ? 200 : 202);
    }

    private function emailDomain(Request $request): ?string
    {
        $email = $request->user()->email;

        return str_contains((string) $email, '@') ? strtolower(substr(strrchr($email, '@'), 1)) : null;
    }
}
