<?php

declare(strict_types=1);

namespace Tests\Support;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Http;

/** Canned API bodies in the exact shape https://rickandmortyapi.com returns. Shared by unit and feature tests. */
trait RickAndMortyFixtures
{
    protected const API = 'https://rickandmortyapi.com/api';

    /** One character. */
    protected function characterData(int $id, string $name): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'status' => 'Alive',
            'species' => 'Human',
            'type' => '',
            'gender' => 'Male',
            'origin' => ['name' => 'Earth (C-137)', 'url' => self::API.'/location/1'],
            'location' => ['name' => 'Citadel of Ricks', 'url' => self::API.'/location/3'],
            'image' => "https://rickandmortyapi.com/api/character/avatar/{$id}.jpeg",
            'episode' => [self::API.'/episode/1', self::API.'/episode/2'],
            'url' => self::API."/character/{$id}",
            'created' => '2017-11-04T18:48:46.250Z',
        ];
    }

    /** One episode. */
    protected function episodeData(int $id, string $name, string $code): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'air_date' => 'December 2, 2013',
            'episode' => $code,
            'characters' => [self::API.'/character/1'],
            'url' => self::API."/episode/{$id}",
            'created' => '2017-11-10T12:56:33.798Z',
        ];
    }

    /** A list page: ["info" => ..., "results" => [...]]. Names are "Character 1" .. "Character N". */
    protected function pageBody(int $results = 20, int $pages = 42, int $count = 826): array
    {
        $list = [];
        for ($i = 1; $i <= $results; $i++) {
            $list[] = $this->characterData($i, "Character {$i}");
        }

        return [
            'info' => ['count' => $count, 'pages' => $pages, 'next' => null, 'prev' => null],
            'results' => $list,
        ];
    }

    /** The body the API sends with a 404 on the list. */
    protected function nothingHere(): array
    {
        return ['error' => 'There is nothing here'];
    }

    /** A stand-in for a JPEG. The client never inspects the bytes, only the Content-Type. */
    protected const JPEG = "\xFF\xD8\xFF fake jpeg bytes";

    /** The API's answer for an avatar image. */
    protected function imageResponse(string $contentType = 'image/jpeg'): PromiseInterface
    {
        return Http::response(self::JPEG, 200, ['Content-Type' => $contentType]);
    }
}
