<?php

declare(strict_types=1);

namespace App\RickAndMorty;

/**
 * Any failure talking to the Rick and Morty API that the application should
 * report as "the API is unavailable right now". Subclasses narrow the reason.
 */
class ApiException extends \RuntimeException {}
