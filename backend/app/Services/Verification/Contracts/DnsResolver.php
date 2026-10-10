<?php

namespace App\Services\Verification\Contracts;

/**
 * Wraps DNS lookups so verification logic is testable without a network.
 */
interface DnsResolver
{
    /**
     * @return list<string> the TXT strings published at $domain
     */
    public function txt(string $domain): array;

    /**
     * @return list<string> the mail exchanger hostnames for $domain
     */
    public function mx(string $domain): array;
}
