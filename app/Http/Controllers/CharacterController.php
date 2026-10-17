<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CharacterListRequest;
use App\RickAndMorty\ApiClient;
use App\RickAndMorty\CharacterPage;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;

final class CharacterController extends Controller
{
    /** The API serves fixed pages of this size. */
    private const PER_PAGE = 20;

    public function __construct(private readonly ApiClient $api) {}

    public function index(CharacterListRequest $request): View
    {
        $result = $this->api->characters($request->page());

        return view('characters.index', [
            'result' => $result,
            'paginator' => $this->paginatorFor($result, $request->url()),
        ]);
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
