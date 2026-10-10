<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates a route on the caller's profile being verified.
 *
 * Applied as `verified` (any verification) or `verified:identity` (the
 * stricter government-ID check). Employers are judged on their company
 * verification, job seekers on their identity verification.
 *
 * Rollout note: enforcement is off unless
 * `verification.employer.require_for_posting` is on, so adding the middleware
 * to a route does not lock out every existing employer the day it ships.
 */
class EnsureIsVerified
{
    public function handle(Request $request, Closure $next, string $level = 'basic'): Response
    {
        if (! config('verification.employer.require_for_posting')) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        if ($user->isEmployer() && ! $user->employerProfile?->is_verified) {
            return $this->deny('Company verification is required before you can post jobs or view candidate profiles.');
        }

        if ($user->isJobSeeker() && $level === 'identity' && ! $user->jobSeekerProfile?->is_identity_verified) {
            return $this->deny('Identity verification is required for this action.');
        }

        return $next($request);
    }

    private function deny(string $message): Response
    {
        return response()->json([
            'message' => $message,
            'code'    => 'VERIFICATION_REQUIRED',
        ], 403);
    }
}
