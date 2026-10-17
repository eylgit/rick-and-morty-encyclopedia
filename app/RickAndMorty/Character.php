<?php

declare(strict_types=1);

namespace App\RickAndMorty;

/** One character, mapped from the API's JSON into named, read-only fields. */
final class Character
{
    /** @param int[] $episodeIds */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $status,
        public readonly string $species,
        public readonly string $type,
        public readonly string $gender,
        public readonly string $origin,
        public readonly string $location,
        public readonly string $image,
        public readonly array $episodeIds,
    ) {}

    /** @param array<string, mixed> $data one entry of the API's "results", or a single-character response */
    public static function fromArray(array $data): self
    {
        if (! isset($data['id'], $data['name'])) {
            throw new ApiException('Character data from the API is missing "id" or "name".');
        }

        if (isset($data['episode']) && ! is_array($data['episode'])) {
            throw new ApiException('Character data from the API has a malformed "episode" list.');
        }

        return new self(
            id: (int) $data['id'],
            name: (string) $data['name'],
            status: (string) ($data['status'] ?? 'unknown'),
            species: (string) ($data['species'] ?? 'unknown'),
            type: (string) ($data['type'] ?? ''),
            gender: (string) ($data['gender'] ?? 'unknown'),
            origin: (string) ($data['origin']['name'] ?? 'unknown'),
            location: (string) ($data['location']['name'] ?? 'unknown'),
            image: (string) ($data['image'] ?? ''),
            // The API lists episodes as URLs ("https://.../episode/28"); we keep only the id.
            episodeIds: array_map(fn ($url) => (int) basename((string) $url), $data['episode'] ?? []),
        );
    }
}
