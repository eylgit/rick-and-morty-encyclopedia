<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\RickAndMorty\ApiClient;
use App\RickAndMorty\NotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Serves character images through the app. The bytes are fetched from the API at most once
 * per cache lifetime; the browser is told to keep them for the same time and to revalidate
 * against this app, not against the API.
 */
final class AvatarController extends Controller
{
    public function __construct(private readonly ApiClient $api) {}

    public function show(Request $request, int $id): Response
    {
        try {
            $bytes = Cache::remember(
                "rickandmorty:avatar:{$id}",
                config('rickandmorty.avatar_cache_seconds'),
                fn () => $this->api->avatar($id),
            );
        } catch (NotFoundException) {
            abort(404, "Avatar {$id} does not exist.");
        }

        $response = response($bytes, 200, ['Content-Type' => 'image/jpeg']);
        $response->setEtag(md5($bytes));
        $response->setPublic();
        $response->setMaxAge((int) config('rickandmorty.avatar_cache_seconds'));
        $response->setImmutable();

        // A matching If-None-Match turns this into an empty 304; the browser keeps its copy.
        $response->isNotModified($request);

        return $response;
    }
}
