<?php

namespace App\Support;

/**
 * Builds links into the Vue SPA.
 *
 * Laravel's url() helper resolves against APP_URL, which is the API — a link
 * built that way lands on a route that doesn't exist. Anything a user clicks
 * from an email or notification belongs to the frontend, so it goes through
 * here instead.
 */
class Frontend
{
    public static function url(string $path = ''): string
    {
        return rtrim((string) config('app.frontend_url'), '/').'/'.ltrim($path, '/');
    }
}
