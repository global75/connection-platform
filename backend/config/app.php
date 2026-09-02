<?php

/*
|--------------------------------------------------------------------------
| Application overrides
|--------------------------------------------------------------------------
|
| Only the keys defined here override Laravel's packaged app config; the rest
| of the framework defaults are merged in underneath.
|
*/

return [

    /*
    | Where the Vue SPA is served. APP_URL points at the API, so links mailed
    | to users must be built against this instead or they 404. Reading it from
    | config rather than env() keeps it correct under `config:cache`.
    */

    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),

];
