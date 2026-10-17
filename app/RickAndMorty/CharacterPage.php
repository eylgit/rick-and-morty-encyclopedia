<?php

declare(strict_types=1);

namespace App\RickAndMorty;

/** One page of the character list plus the paging numbers the API reports. */
final class CharacterPage
{
    /** @param Character[] $characters */
    public function __construct(
        public readonly array $characters,
        public readonly int $page,
        public readonly int $pages,
        public readonly int $count,
    ) {}

    /** @param array<string, mixed> $data the API's list response: ["info" => [...], "results" => [...]] */
    public static function fromArray(array $data, int $page): self
    {
        return new self(
            characters: array_map(fn ($row) => Character::fromArray(ApiClient::row($row, 'character')), $data['results']),
            page: $page,
            pages: (int) ($data['info']['pages'] ?? 0),
            count: (int) ($data['info']['count'] ?? 0),
        );
    }

    /** The API answers 404 when a search matches nothing; we represent that as an empty page. */
    public static function noResults(int $page): self
    {
        return new self(characters: [], page: $page, pages: 0, count: 0);
    }

    public function isEmpty(): bool
    {
        return $this->characters === [];
    }

    public function hasPrevious(): bool
    {
        return $this->page > 1;
    }

    public function hasNext(): bool
    {
        return $this->page < $this->pages;
    }
}
