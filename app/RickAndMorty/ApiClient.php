<?php

declare(strict_types=1);

namespace App\RickAndMorty;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * All communication with the Rick and Morty REST API (https://rickandmortyapi.com/documentation).
 *
 * Three public methods, one private request helper. Every answer is mapped to our own objects
 * and every failure to our own exceptions, so nothing outside this folder knows about HTTP.
 */
final class ApiClient
{
    /** The filter keys documented for /character. Anything else is never forwarded. */
    private const FILTER_KEYS = ['name', 'status', 'species', 'type', 'gender'];

    private const CONNECT_TIMEOUT_SECONDS = 3;

    public function __construct(
        private readonly string $baseUrl,
        private readonly int $timeout = 5,
    ) {}

    /**
     * One page of characters (20 per page), optionally filtered.
     *
     * @param  array<string, string|null>  $filters  name, status, species, type, gender
     */
    public function characters(int $page = 1, array $filters = []): CharacterPage
    {
        $query = ['page' => $page];
        foreach (self::FILTER_KEYS as $key) {
            $value = trim((string) ($filters[$key] ?? ''));
            if ($value !== '') {
                $query[$key] = $value;
            }
        }

        $response = $this->get('character', $query);

        // The API answers 404 for a search that matches nothing. That is an empty page, not an error.
        if ($response->status() === 404) {
            return CharacterPage::noResults($page);
        }

        $data = $this->decode($response);
        if (! isset($data['info'], $data['results']) || ! is_array($data['results'])) {
            throw new ApiException('The character list response is missing "info" or "results".');
        }

        return CharacterPage::fromArray($data, $page);
    }

    public function character(int $id): Character
    {
        $response = $this->get("character/{$id}");

        if ($response->status() === 404) {
            throw new NotFoundException("Character {$id} does not exist.");
        }

        return Character::fromArray($this->decode($response));
    }

    /**
     * Several episodes in one request: /episode/1,2,3.
     *
     * @param  int[]  $ids
     * @return Episode[]
     */
    public function episodes(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $response = $this->get('episode/'.implode(',', $ids));

        if ($response->status() === 404) {
            throw new ApiException('The Rick and Morty API has no episodes for ids '.implode(',', $ids).'.');
        }

        $data = $this->decode($response);

        // One id gives a single object, several give a list. Normalise to a list.
        if (isset($data['id'])) {
            $data = [$data];
        }

        return array_map(fn ($row) => Episode::fromArray(self::row($row, 'episode')), $data);
    }

    /**
     * Send one GET and translate failures into our own exceptions.
     * 404 is returned to the caller, because its meaning depends on what was asked for.
     *
     * @param  array<string, int|string>  $query
     */
    private function get(string $path, array $query = []): Response
    {
        try {
            $response = Http::baseUrl($this->baseUrl)
                ->timeout($this->timeout)
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                ->acceptJson()
                ->withUserAgent('rick-and-morty-encyclopedia')
                ->get($path, $query);
        } catch (ConnectionException $e) {
            throw new ApiException('Could not reach the Rick and Morty API: '.$e->getMessage(), previous: $e);
        }

        if ($response->status() === 429) {
            throw new RateLimitedException('The Rick and Morty API is rate-limiting this application.');
        }

        if ($response->status() !== 404 && ! $response->successful()) {
            throw new ApiException("The Rick and Morty API answered HTTP {$response->status()}.");
        }

        return $response;
    }

    /**
     * A row of a list must itself be a JSON object; anything else means the API changed shape.
     *
     * @return array<string, mixed>
     */
    public static function row(mixed $row, string $what): array
    {
        if (! is_array($row)) {
            throw new ApiException("A {$what} entry from the Rick and Morty API is not an object.");
        }

        return $row;
    }

    /** @return array<string, mixed> */
    private function decode(Response $response): array
    {
        $data = $response->json();

        if (! is_array($data)) {
            throw new ApiException('The Rick and Morty API returned a body that is not JSON.');
        }

        return $data;
    }
}
