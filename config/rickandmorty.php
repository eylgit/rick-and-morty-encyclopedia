<?php

/*
 * Settings for talking to the Rick and Morty API. Override any value in .env.
 */
return [
    'base_url' => env('RICK_AND_MORTY_API_URL', 'https://rickandmortyapi.com/api'),

    // Seconds allowed for one request, connect included.
    'timeout' => (int) env('RICK_AND_MORTY_API_TIMEOUT', 5),

    // How long an API answer is reused before asking again. The API itself marks its
    // data immutable for 90 days, so an hour is conservative.
    'cache_seconds' => (int) env('RICK_AND_MORTY_CACHE_SECONDS', 3600),
];
