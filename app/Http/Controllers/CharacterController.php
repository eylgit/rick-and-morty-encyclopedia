<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CharacterListRequest;
use App\RickAndMorty\ApiClient;
use App\RickAndMorty\CharacterPage;
use App\RickAndMorty\NotFoundException;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

final class CharacterController extends Controller
{
    /** The API serves fixed pages of this size. */
    private const PER_PAGE = 20;

    public function __construct(private readonly ApiClient $api) {}

    public function index(CharacterListRequest $request): View
    {
        $page = $request->page();
        $filters = $request->filters();

        // The key is built from validated input in a fixed order, so the same search
        // written in a different parameter order, or with empty fields, hits the same entry.
        $result = $this->remember(
            'list:'.http_build_query(['page' => $page] + $filters),
            fn () => $this->api->characters($page, $filters),
        );

        return view('characters.index', [
            'result' => $result,
            'filters' => $filters,
            // Page links carry only the validated filters, never the raw query string.
            'paginator' => $this->paginatorFor($result, $request->url())->appends($filters),
        ]);
    }

    public function show(int $id): View
    {
        try {
            $character = $this->remember("character:{$id}", fn () => $this->api->character($id));
        } catch (NotFoundException) {
            abort(404, "Character {$id} does not exist.");
        }

        return view('characters.show', [
            'character' => $character,
            // All episodes in one API call; none at all if the character has no episodes.
            'episodes' => $this->remember(
                'episodes:'.implode(',', $character->episodeIds),
                fn () => $this->api->episodes($character->episodeIds),
            ),
        ]);
    }

    /**
     * Reuse a stored API answer, or fetch and store it. An exception thrown by the
     * fetch leaves nothing in the cache, so a failure is never remembered.
     */
    private function remember(string $key, Closure $fetch): mixed
    {
        return Cache::remember('rickandmorty:'.$key, config('rickandmorty.cache_seconds'), $fetch);
    }

    /**
     * The API does the paging; Laravel's paginator only draws the links.
     * It needs the items of this page, the total, the page size and the current page.
     */
    private function paginatorFor(CharacterPage $result, string $path): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            items: $result->characters,
            total: $result->count,
            perPage: self::PER_PAGE,
            currentPage: $result->page,
            options: ['path' => $path],
        );
    }
}
