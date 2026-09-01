<?php

namespace App\Services\Verification\Checkers;

use App\Models\JobSeekerProfile;
use App\Services\Verification\CheckResult;
use App\Services\Verification\Contracts\VerificationChecker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

/**
 * Confirms a candidate controls a GitHub account, and records its shape:
 * account age, public repo count, followers.
 *
 * Ownership is what the OAuth round-trip proves. The age and repo thresholds
 * are reported as signals — a young account is annotated, not rejected, since
 * plenty of capable developers keep their work private.
 */
class GithubOauthChecker implements VerificationChecker
{
    public function type(): string
    {
        return 'github_oauth';
    }

    public function available(): bool
    {
        return filled(config('verification.providers.github.client_id'))
            && filled(config('verification.providers.github.client_secret'));
    }

    public function check(Model $subject, array $input = []): CheckResult
    {
        if (! $subject instanceof JobSeekerProfile) {
            throw new InvalidArgumentException('GitHub verification applies to job seeker profiles.');
        }

        $code = $input['code'] ?? null;

        if (blank($code)) {
            return CheckResult::rejected('github', 'No OAuth authorisation code was supplied.');
        }

        $token = $this->exchangeCode((string) $code);

        if ($token === null) {
            return CheckResult::rejected('github', 'GitHub rejected the authorisation code.');
        }

        $account = $this->fetchAccount($token);

        if ($account === null) {
            return CheckResult::rejected('github', 'Could not read the GitHub account for that authorisation.');
        }

        $createdAt = Carbon::parse($account['created_at']);
        $ageDays   = (int) $createdAt->diffInDays(now());
        $repos     = (int) ($account['public_repos'] ?? 0);

        return CheckResult::approved(
            'github',
            [
                'login'             => $account['login'] ?? null,
                'account_age_days'  => $ageDays,
                'public_repos'      => $repos,
                'followers'         => (int) ($account['followers'] ?? 0),
                'created_at'        => $createdAt->toIso8601String(),
                // Signals for a recruiter to weigh, not pass/fail gates.
                'meets_age_signal'  => $ageDays >= (int) config('verification.candidate.github_min_account_age_days'),
                'meets_repo_signal' => $repos >= (int) config('verification.candidate.github_min_public_repos'),
            ],
            externalId: isset($account['id']) ? (string) $account['id'] : null,
        );
    }

    private function exchangeCode(string $code): ?string
    {
        $response = Http::asForm()
            ->acceptJson()
            ->post('https://github.com/login/oauth/access_token', [
                'client_id'     => config('verification.providers.github.client_id'),
                'client_secret' => config('verification.providers.github.client_secret'),
                'code'          => $code,
            ]);

        return $response->successful() ? ($response->json('access_token') ?: null) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchAccount(string $token): ?array
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->withHeaders(['X-GitHub-Api-Version' => '2022-11-28'])
            ->get(rtrim((string) config('verification.providers.github.api_url'), '/').'/user');

        if (! $response->successful() || blank($response->json('created_at'))) {
            return null;
        }

        return $response->json();
    }
}
