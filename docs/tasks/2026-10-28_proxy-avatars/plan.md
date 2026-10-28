# Plan: serve character images through the app

Status: **planned** (2026-10-28). Companion file: [test-cases.md](test-cases.md).

## Why

The character pages put the API's own image URLs into `<img>` tags, so the browser fetches every
avatar straight from `rickandmortyapi.com`. The API marks images immutable for 90 days, so normal
browsing never re-fetches them. A hard reload does: the browser revalidates all 20 images on the
page, the API's CDN counts every 304, and after a few dozen reloads it answers 429 and the browser
shows broken images. The JSON never breaks, because `Cache::remember` sits in front of it. The
images had no such layer.

## Goal

One new route, `/avatars/{id}`, that fetches an image from the API at most once per cache
lifetime and serves it with long cache headers. The browser then talks only to this app.

## Exit criteria

- [ ] `/avatars/1` returns `image/jpeg` with `Cache-Control: public, max-age=7776000, immutable` and an `ETag`.
- [ ] A second request for the same id makes no API request (served from the cache).
- [ ] A conditional request with the matching `ETag` gets 304 and makes no API request.
- [ ] The list and detail pages point every `<img>` at `/avatars/{id}`, never at the API.
- [ ] A 429, 5xx or connection failure upstream gives the existing 503 page and caches nothing.
- [ ] `/avatars/abc` and an unknown id are 404.
- [ ] `php artisan test` green; Pint clean; README and `docs/reference` updated.

## Decisions

| Decision | Choice | One-line why |
|---|---|---|
| What the URL carries | A character id, digits only; the client builds `character/avatar/{id}.jpeg` itself | A URL parameter would make the app an open proxy that fetches anything a visitor names |
| Where the bytes live | `Cache::remember`, same store and helper as the JSON, value is the raw string | One mechanism to explain; a string needs no allow-list entry |
| Lifetime | New config key `avatar_cache_seconds`, default 90 days (`7776000`), overridable in `.env` | Images are immutable upstream for 90 days; the one-hour JSON lifetime would re-fetch 826 images an hour under load |
| Who fetches | Fourth public method on `ApiClient`: `avatar(int $id): string` | Reuses `get()`: base URL, timeouts, user agent, and the 429/5xx/connection mapping |
| Body check | The upstream `Content-Type` must start with `image/`, else `ApiException` | An HTML error page must never be cached and served as a JPEG |
| 404 upstream | `NotFoundException`, the controller turns it into our 404 page | Same meaning as a missing character |
| Failure | Any `ApiException` escapes to the existing render rule; the `<img>` shows broken, nothing is cached | Consistent with the rest of the app; no new error page |
| Headers served | `Content-Type: image/jpeg`, `Cache-Control: public, max-age=7776000, immutable`, `ETag` from the bytes; 304 on a matching `If-None-Match` | Without these every page view would pull 20 images through PHP; with them a hard reload costs 20 cheap 304s from this app and nothing from the API |
| Who serves hits | PHP, via the route | Matches `artisan serve`; production would write files under `public/avatars` so nginx serves them, noted in the README with the nginx line |
| Simultaneous misses | Accepted | Both fetch and store the same bytes; one duplicate request per image per cold cache |
| `Character::$image` | Kept, unused by the views | Nothing that parses the API changes |

## Build steps

Each step: tests first, then code, then one commit, then a review before the next step.

| # | Step | Visible result when done | Exit criteria |
|---|---|---|---|
| 1 | This plan and the test list | `git log` shows the plan before any code | Committed |
| 2a | Failing tests: client `avatar()` (A1 to A5) and the route (P1 to P7) | `php artisan test` shows 12 failures | Committed red |
| 2b | `avatar()` on the client, config key, route, `AvatarController`, views point at `/avatars/{id}` | Browser: hard reload of `/characters` never requests `rickandmortyapi.com` for images | A1 to A5, P1 to P7 green; committed |
| 3 | README, `docs/reference/architecture.md`, `decisions.md`, `rick-and-morty-api.md`, `testing.md` | Docs describe the route and the headers | Committed |

## The request, top to bottom

```
GET /avatars/1
  |
  v
routes/web.php ......................... /avatars/{id}, digits only
  |
  v
app/Http/Controllers/AvatarController .. Cache::remember('rickandmorty:avatar:1', 90 d, fn => client)
  |                                         |
  |                                         v
  |                                  app/RickAndMorty/ApiClient::avatar(1) .. GET character/avatar/1.jpeg
  |                                         |   404 -> NotFoundException; 429/5xx/connection -> ApiException
  |                                         |   Content-Type not image/* -> ApiException; else the bytes
  v                                         v
response: image/jpeg, Cache-Control public max-age=7776000 immutable, ETag; 304 if If-None-Match matches
```
