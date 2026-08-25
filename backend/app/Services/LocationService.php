<?php

namespace App\Services;

use App\Support\Location\LocationCatalog;
use Illuminate\Support\Str;

/**
 * Resolves human-entered places into normalised, comparable location data.
 *
 * Three concepts are kept deliberately separate across the platform:
 *   1. Location          — where the job (or person) physically is
 *   2. Work arrangement  — how the work is performed (on-site / hybrid / remote)
 *   3. Hiring eligibility— who is allowed to apply
 * This service only deals with (1).
 */
class LocationService
{
    public const EARTH_RADIUS_MILES = 3958.8;

    /**
     * Normalise a country name or code to an ISO-3166 alpha-2 code.
     * Returns null when the value is not recognised, so callers can keep the
     * raw text instead of inventing a code.
     */
    public function normalizeCountry(?string $country): ?string
    {
        if (blank($country)) {
            return null;
        }

        $raw  = trim($country);
        $code = strtoupper($raw);

        if (isset(LocationCatalog::COUNTRIES[$code])) {
            return $code;
        }

        $lower = Str::lower($raw);

        if (isset(LocationCatalog::COUNTRY_ALIASES[$lower])) {
            return LocationCatalog::COUNTRY_ALIASES[$lower];
        }

        foreach (LocationCatalog::COUNTRIES as $iso => $name) {
            if (Str::lower($name) === $lower) {
                return $iso;
            }
        }

        return null;
    }

    public function countryName(?string $country): ?string
    {
        $code = $this->normalizeCountry($country);

        return $code ? LocationCatalog::COUNTRIES[$code] : ($country ?: null);
    }

    /**
     * Normalise a state / province to its official code within a country.
     * Unknown values are returned trimmed but otherwise untouched.
     */
    public function normalizeState(?string $state, ?string $country = null): ?string
    {
        if (blank($state)) {
            return null;
        }

        $raw     = trim($state);
        $country = $this->normalizeCountry($country);
        $sets    = $country && isset(LocationCatalog::STATES[$country])
            ? [$country => LocationCatalog::STATES[$country]]
            : LocationCatalog::STATES;

        foreach ($sets as $states) {
            $code = strtoupper($raw);
            if (isset($states[$code])) {
                return $code;
            }
            foreach ($states as $iso => $name) {
                if (Str::lower($name) === Str::lower($raw)) {
                    return $iso;
                }
            }
        }

        return $raw;
    }

    public function stateName(?string $state, ?string $country = null): ?string
    {
        $country = $this->normalizeCountry($country);
        $code    = $this->normalizeState($state, $country);

        if ($country && isset(LocationCatalog::STATES[$country][$code])) {
            return LocationCatalog::STATES[$country][$code];
        }

        foreach (LocationCatalog::STATES as $states) {
            if (isset($states[$code])) {
                return $states[$code];
            }
        }

        return $code;
    }

    /**
     * Look up real coordinates for a city. Returns null when the city is not in
     * the catalog — callers must treat that as "no coordinates", never as an
     * approximation.
     *
     * @return array{latitude: float, longitude: float, city: string, state: ?string, country: string}|null
     */
    public function coordinatesFor(?string $city, ?string $state = null, ?string $country = null): ?array
    {
        if (blank($city)) {
            return null;
        }

        $city    = Str::lower(trim($city));
        $country = $this->normalizeCountry($country);
        $state   = $state ? $this->normalizeState($state, $country) : null;
        $partial = null;

        foreach (LocationCatalog::CITIES as [$name, $st, $co, $lat, $lng]) {
            if (Str::lower($name) !== $city) {
                continue;
            }

            $match = [
                'city' => $name, 'state' => $st, 'country' => $co,
                'latitude' => $lat, 'longitude' => $lng,
            ];

            if ($country && $country !== $co) {
                continue;
            }
            if ($state && $st && $state !== $st) {
                continue;
            }
            if ($state && $st && $state === $st) {
                return $match;
            }

            $partial ??= $match;
        }

        return $partial;
    }

    /**
     * Parse a free-text location box ("Denver, CO", "Toronto, Canada", "Remote").
     *
     * @return array{city: ?string, state: ?string, country: ?string, latitude: ?float, longitude: ?float, remote: bool}
     */
    public function parse(?string $input): array
    {
        $empty = ['city' => null, 'state' => null, 'country' => null,
                  'latitude' => null, 'longitude' => null, 'remote' => false];

        if (blank($input)) {
            return $empty;
        }

        $input = trim($input);

        if (in_array(Str::lower($input), ['remote', 'anywhere', 'worldwide'], true)) {
            return array_merge($empty, ['remote' => true]);
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $input)), fn ($p) => $p !== ''));
        $city  = $parts[0] ?? null;

        // "Canada" / "United States" on its own is a country, not a city.
        if (count($parts) === 1 && ($code = $this->normalizeCountry($city))) {
            return array_merge($empty, ['country' => $code]);
        }

        // A single token that is a known state ("Colorado") is a state search.
        if (count($parts) === 1) {
            foreach (LocationCatalog::STATES as $iso => $states) {
                $asState = $this->normalizeState($city, $iso);
                if (isset($states[$asState])) {
                    return array_merge($empty, ['state' => $asState, 'country' => $iso]);
                }
            }
        }

        // "Denver, CO" is ambiguous on its own — CO is both Colorado and
        // Colombia. Try each reading and keep the one that resolves to a real
        // place, rather than guessing.
        foreach ($this->interpretations($parts) as [$state, $country]) {
            $state  = $state ? $this->normalizeState($state, $country) : null;
            $coords = $this->coordinatesFor($city, $state, $country);

            if ($coords) {
                return [
                    'city'      => $coords['city'],
                    'state'     => $coords['state'],
                    'country'   => $coords['country'],
                    'latitude'  => $coords['latitude'],
                    'longitude' => $coords['longitude'],
                    'remote'    => false,
                ];
            }
        }

        // Nothing in the catalog matched: keep what the user typed, without
        // attaching coordinates we do not have.
        [$state, $country] = $this->interpretations($parts)[0];

        return array_merge($empty, [
            'city'    => $city,
            'state'   => $state ? $this->normalizeState($state, $country) : null,
            'country' => $country,
        ]);
    }

    /**
     * Possible (state, country) readings of the tokens after the city, most
     * likely first.
     *
     * @param  array<int, string> $parts
     * @return array<int, array{0: ?string, 1: ?string}>
     */
    private function interpretations(array $parts): array
    {
        $second = $parts[1] ?? null;
        $third  = $parts[2] ?? null;

        if ($third) {
            return [[$second, $this->normalizeCountry($third) ?? $third]];
        }

        $readings = [[$second, null]]; // "Denver, CO" → Colorado

        if ($second && ($country = $this->normalizeCountry($second))) {
            $readings[] = [null, $country]; // "Toronto, Canada"
        }

        return $readings;
    }

    /** Great-circle distance in miles between two points. */
    public function distanceMiles(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return self::EARTH_RADIUS_MILES * 2 * asin(min(1.0, sqrt($a)));
    }

    /** URL slug for a location page, e.g. "denver-co", "colorado", "canada". */
    public function slugFor(?string $city, ?string $state = null, ?string $country = null): ?string
    {
        if ($city) {
            return Str::slug($state ? "{$city} {$state}" : $city);
        }
        if ($state) {
            return Str::slug($this->stateName($state, $country) ?? $state);
        }
        if ($country) {
            return Str::slug($this->countryName($country) ?? $country);
        }

        return null;
    }

    /**
     * Reverse a location slug back into filter criteria.
     *
     * @return array{city: ?string, state: ?string, country: ?string}|null
     */
    public function fromSlug(string $slug): ?array
    {
        $slug = Str::lower(trim($slug));

        foreach (LocationCatalog::COUNTRIES as $iso => $name) {
            if (Str::slug($name) === $slug) {
                return ['city' => null, 'state' => null, 'country' => $iso];
            }
        }

        foreach (LocationCatalog::STATES as $iso => $states) {
            foreach ($states as $code => $name) {
                if (Str::slug($name) === $slug) {
                    return ['city' => null, 'state' => $code, 'country' => $iso];
                }
            }
        }

        foreach (LocationCatalog::CITIES as [$name, $st, $co, ,]) {
            if (Str::slug($st ? "{$name} {$st}" : $name) === $slug || Str::slug($name) === $slug) {
                return ['city' => $name, 'state' => $st, 'country' => $co];
            }
        }

        return null;
    }

    /** Human label for a slug-driven page, e.g. "Denver, CO". */
    public function labelFor(?string $city, ?string $state = null, ?string $country = null): string
    {
        if ($city) {
            return collect([$city, $state])->filter()->implode(', ');
        }
        if ($state) {
            return $this->stateName($state, $country) ?? $state;
        }

        return $this->countryName($country) ?? 'Anywhere';
    }

    /** @return array<int, array{code: string, name: string}> */
    public function countries(): array
    {
        return collect(LocationCatalog::COUNTRIES)
            ->map(fn ($name, $code) => ['code' => $code, 'name' => $name])
            ->sortBy('name')
            ->values()
            ->all();
    }

    /** @return array<int, array{code: string, name: string}> */
    public function statesFor(?string $country): array
    {
        $country = $this->normalizeCountry($country);

        return collect(LocationCatalog::STATES[$country] ?? [])
            ->map(fn ($name, $code) => ['code' => $code, 'name' => $name])
            ->values()
            ->all();
    }
}
