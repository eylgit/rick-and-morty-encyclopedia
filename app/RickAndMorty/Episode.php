<?php

declare(strict_types=1);

namespace App\RickAndMorty;

/** One episode: id, title, code such as "S01E01", and the air date as the API gives it. */
final class Episode
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $code,
        public readonly string $airDate,
    ) {}

    /** @param array<string, mixed> $data one episode object from the API */
    public static function fromArray(array $data): self
    {
        if (! isset($data['id'], $data['name'])) {
            throw new ApiException('Episode data from the API is missing "id" or "name".');
        }

        return new self(
            id: (int) $data['id'],
            name: (string) $data['name'],
            code: (string) ($data['episode'] ?? ''),
            airDate: (string) ($data['air_date'] ?? ''),
        );
    }
}
