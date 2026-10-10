<?php

namespace App\Services\Verification\Contracts;

use App\Services\Verification\CheckResult;
use Illuminate\Database\Eloquent\Model;

interface VerificationChecker
{
    /**
     * The `verifications.type` this checker produces.
     */
    public function type(): string;

    /**
     * False when the checker cannot run — usually missing credentials. The API
     * surfaces this as 503 rather than recording a bogus rejection.
     */
    public function available(): bool;

    /**
     * @param  array<string, mixed>  $input  Caller-supplied data (token, code, number…).
     */
    public function check(Model $subject, array $input = []): CheckResult;
}
