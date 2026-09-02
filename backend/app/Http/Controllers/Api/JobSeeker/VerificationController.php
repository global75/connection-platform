<?php

namespace App\Http\Controllers\Api\JobSeeker;

use App\Http\Controllers\Controller;
use App\Models\Verification;
use App\Services\Verification\VerificationService;
use App\Services\Verification\VerificationUnavailable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function __construct(private VerificationService $verifications) {}

    public function show(Request $request): JsonResponse
    {
        $seeker = $request->user()->jobSeekerProfile;
        $seeker->load('verifications');

        return response()->json([
            'verification' => [
                'is_identity_verified' => (bool) $seeker->is_identity_verified,
                'identity_verified_at' => $seeker->identity_verified_at,
                'badges'               => $seeker->activeBadges(),
                'available_types'      => $this->verifications->availableTypes($seeker),
                'records'              => $seeker->verifications->map(fn (Verification $v) => [
                    'type'             => $v->type,
                    'status'           => $v->status,
                    'provider'         => $v->provider,
                    'metadata'         => $v->metadata,
                    'verified_at'      => $v->verified_at,
                    'expires_at'       => $v->expires_at,
                    'rejection_reason' => $v->rejection_reason,
                ])->values(),
            ],
        ]);
    }

    /**
     * Complete a verification for this candidate — e.g. hand back the GitHub
     * OAuth code after the redirect.
     */
    public function store(Request $request): JsonResponse
    {
        $seeker = $request->user()->jobSeekerProfile;

        $validated = $request->validate([
            'type' => ['required', 'in:'.implode(',', Verification::CANDIDATE_TYPES)],
            'code' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $verification = $this->verifications->verify($seeker, $validated['type'], $validated);
        } catch (VerificationUnavailable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code'    => 'VERIFICATION_PROVIDER_UNAVAILABLE',
            ], 503);
        }

        return response()->json([
            'verification' => $verification,
            'badges'       => $seeker->fresh()->verified_badges ?? [],
        ], $verification->isActive() ? 200 : 202);
    }
}
