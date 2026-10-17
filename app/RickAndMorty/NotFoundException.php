<?php

declare(strict_types=1);

namespace App\RickAndMorty;

/** A single resource (one character) does not exist. Not thrown for empty lists. */
final class NotFoundException extends ApiException {}
