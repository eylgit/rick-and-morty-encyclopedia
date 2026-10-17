<?php

declare(strict_types=1);

namespace App\RickAndMorty;

/** The API answered HTTP 429: we are sending requests too quickly. */
final class RateLimitedException extends ApiException {}
