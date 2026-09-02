<?php

namespace App\Services\Verification;

use App\Services\Verification\Contracts\DnsResolver;

class SystemDnsResolver implements DnsResolver
{
    public function txt(string $domain): array
    {
        return $this->lookup($domain, DNS_TXT, 'txt');
    }

    public function mx(string $domain): array
    {
        return $this->lookup($domain, DNS_MX, 'target');
    }

    /**
     * @return list<string>
     */
    private function lookup(string $domain, int $type, string $field): array
    {
        // dns_get_record emits a warning and returns false for NXDOMAIN and on
        // resolver failure; an empty list is the right answer either way.
        $records = @dns_get_record($domain, $type);

        if ($records === false) {
            return [];
        }

        return collect($records)
            ->pluck($field)
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->values()
            ->all();
    }
}
