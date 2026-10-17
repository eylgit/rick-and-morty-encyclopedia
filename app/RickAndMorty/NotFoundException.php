<?php

declare(strict_types=1);

namespace App\RickAndMorty;

/**
 * A single resource (one character) does not exist. Not thrown for empty lists,
 * and deliberately not an ApiException: the API worked, the thing is simply absent.
 */
final class NotFoundException extends \RuntimeException {}
