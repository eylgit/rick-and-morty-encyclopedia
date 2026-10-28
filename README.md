# Rick and Morty Encyclopedia

A small Laravel 13 application that browses the characters of Rick and Morty through the public
[Rick and Morty API](https://rickandmortyapi.com): a paginated, searchable, filterable list and a
detail page per character with the episodes they appear in.

Requirements it was built to: a paginated, searchable, filterable character list; a detail page
with image, species, origin and episodes; every API call hand-written against the REST API with no
Rick-and-Morty-specific library; and sensible behaviour under the API's rate limit.

## Run it

Requires PHP 8.4+ and Composer. No database, no Node.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
```

Then open <http://localhost:8000>.

Optional, once: fill the image cache so no page ever waits for the API's images or trips its
rate limit. About 7 minutes, two requests a second; safe to re-run, it skips what it has.

```bash
php artisan avatars:warm
```

With Docker instead (nothing else installed):

```bash
docker compose up --build
```

## Run the tests

```bash
php artisan test
```

89 tests, one of them skipped by default. None touch the network: every API answer is faked.
The skipped one runs the client against the live API on demand:

```bash
RM_LIVE_TEST=1 php artisan test --filter LiveApiTest
```

`vendor/bin/pint --test` checks code style. Both run on every push in
[.github/workflows/ci.yml](.github/workflows/ci.yml).

## How it is built

Plain Laravel MVC, used thinly:

| Piece | Where |
|---|---|
| API client (all communication with the API, hand-written on Laravel's HTTP client) | [`app/RickAndMorty/`](app/RickAndMorty/) |
| Input validation (page, name, status, species, gender) | [`app/Http/Requests/CharacterListRequest.php`](app/Http/Requests/CharacterListRequest.php) |
| Controllers: validated input in, API answer out, cached; images fetched once and served with long cache headers | [`app/Http/Controllers/`](app/Http/Controllers/) |
| Views: Blade + Bootstrap 5 and IBM Plex Sans from CDNs | [`resources/views/`](resources/views/) |
| API failures become one 503 page | [`bootstrap/app.php`](bootstrap/app.php) |
| Settings (base URL, timeout, cache lifetimes) | [`config/rickandmorty.php`](config/rickandmorty.php), overridable in `.env` |

More in `docs/reference/`: [architecture](docs/reference/architecture.md) ·
[decisions](docs/reference/decisions.md) · [the API as observed](docs/reference/rick-and-morty-api.md) ·
[testing](docs/reference/testing.md). The build plan and test list are in
[docs/tasks/](docs/tasks/2026-10-17_build-encyclopedia/plan.md); the follow-up that moved images
behind the app is in [docs/tasks/2026-10-28_proxy-avatars/](docs/tasks/2026-10-28_proxy-avatars/plan.md),
and the warm-up command in [docs/tasks/2026-10-28_warm-avatar-cache/](docs/tasks/2026-10-28_warm-avatar-cache/plan.md).

## Decisions in short

- **Multi-page, state in the URL.** `/characters?name=rick&status=alive&page=2` is the whole
  state, so refresh, back, bookmark and share work with no JavaScript.
- **The API pages, Laravel only draws the links.** 20 per page is the API's fixed size.
- **Cache each API answer for an hour**, keyed by the validated input. The API marks its data
  immutable for 90 days and is rate limited, so this is both safe and necessary. A failure is
  never cached.
- **Character images are served through the app** at `/avatars/{id}`: fetched from the API once,
  cached for 90 days, sent with `Cache-Control: immutable` and an `ETag`. A browser reload
  revalidates against this app and gets 304s; the API's rate limit never sees the images.
  `php artisan avatars:warm` fills the cache in advance, two images a second, and stops on a 429
  rather than pushing into a ban.
- **No database.** The data lives in the API; a copy would add a sync problem and setup for no gain.
- **Resilience:** timeouts, 429 and 5xx and malformed bodies all end on a friendly 503 page; a
  search with no match is an empty page, not an error; unknown ids are a proper 404.
- **Input:** the known query parameters are validated against whitelists before any code runs and
  unknown ones are ignored; all output is escaped by Blade; page links carry only validated
  parameters.

## Out of scope

Authentication, database, Vue/Inertia, JavaScript pagination, browsing by episode or location,
retry on rate limit (it would make the limit worse), static analysis. Each adds setup, moving
parts or load on a rate-limited API that this scope does not repay.

## Known limits

- The API's latency is the floor for a page nobody has viewed in the last hour.
- A page nobody has opened fetches up to 20 images in a burst, and the API has answered 429 to
  such bursts; `avatars:warm` removes that path. Images pass through PHP on a cache miss and on every 304. In production the files would be
  written under `public/avatars/` so nginx serves them and the route only fills the gaps.
- `php artisan serve` (also used in the Docker image) is a development server. Production would
  run php-fpm behind nginx, with `APP_DEBUG=false` and a shared cache such as Redis
  (one `.env` change: `CACHE_STORE=redis`).

## Licence and trademarks

MIT, see [LICENSE](LICENSE). Unofficial fan project, not affiliated with the show or its owners;
Rick and Morty is a trademark of its owners. Data from [rickandmortyapi.com](https://rickandmortyapi.com).
