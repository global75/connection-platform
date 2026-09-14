<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Service lead notifications
    |--------------------------------------------------------------------------
    |
    | Where inbound leads from the marketing service pages (e.g. the SaaS
    | localization audit form) are sent. Falls back to the app's own from
    | address so a fresh install has somewhere to deliver them.
    |
    */

    'notify_email' => env('LEADS_NOTIFY_EMAIL', env('MAIL_FROM_ADDRESS', 'hello@remotearena.io')),

];
