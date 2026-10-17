<?php

/*
 * Settings for talking to the Rick and Morty API. Override any value in .env.
 */
return [
    'base_url' => env('RICK_AND_MORTY_API_URL', 'https://rickandmortyapi.com/api'),

    // Seconds allowed for one request, connect included.
    'timeout' => (int) env('RICK_AND_MORTY_API_TIMEOUT', 5),
];
