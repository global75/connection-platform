<?php

namespace App\Services\Verification;

use RuntimeException;

/**
 * A verification type was requested that this deployment cannot perform —
 * missing credentials, or no driver bound for it.
 */
class VerificationUnavailable extends RuntimeException
{
}
