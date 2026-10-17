# Rick and Morty Encyclopedia

A small Laravel 13 application that browses the characters of Rick and Morty through the public
[Rick and Morty API](https://rickandmortyapi.com): a paginated, searchable, filterable list and a
detail page per character with the episodes they appear in.

Built for the PHP developer assessment in [docs/ASSESSMENT.md](docs/ASSESSMENT.md).

## Run it

Requires PHP 8.3+ and Composer. No database, no Node.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
```

Then open <http://localhost:8000>.

With Docker instead (nothing else installed):

```bash
docker compose up --build
```

## Run the tests

```bash
php artisan test
```

64 tests, one of them skipped by default. None touch the network: every API answer is faked.
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
| Controller: validated input in, API answer out, cached | [`app/Http/Controllers/CharacterController.php`](app/Http/Controllers/CharacterController.php) |
| Views: Blade + Bootstrap 5 from a CDN | [`resources/views/`](resources/views/) |
| API failures become one 503 page | [`bootstrap/app.php`](bootstrap/app.php) |
| Settings (base URL, timeout, cache lifetime) | [`config/rickandmorty.php`](config/rickandmorty.php), overridable in `.env` |

More in `docs/reference/`: [architecture](docs/reference/architecture.md) ·
[decisions](docs/reference/decisions.md) · [the API as observed](docs/reference/rick-and-morty-api.md) ·
[testing](docs/reference/testing.md). The build plan and test list are in
[docs/tasks/](docs/tasks/2026-10-17_build-encyclopedia/plan.md).

## Decisions in short

- **Multi-page, state in the URL.** `/characters?name=rick&status=alive&page=2` is the whole
  state, so refresh, back, bookmark and share work with no JavaScript.
- **The API pages, Laravel only draws the links.** 20 per page is the API's fixed size.
- **Cache each API answer for an hour**, keyed by the validated input. The API marks its data
  immutable for 90 days and is rate limited, so this is both safe and necessary. A failure is
  never cached.
- **No database.** The data lives in the API; a copy would add a sync problem and setup for no gain.
- **Resilience:** timeouts, 429 and 5xx and malformed bodies all end on a friendly 503 page; a
  search with no match is an empty page, not an error; unknown ids are a proper 404.
- **Input:** every query parameter is validated against a whitelist before any code runs; all
  output is escaped by Blade; page links carry only validated parameters.

## Deliberately left out

Authentication, database, Vue/Inertia, JavaScript pagination, browsing by episode or location,
retry on rate limit (it would make the limit worse), static analysis. Each would be a separate
decision with its own cost; none was asked for.

## Known limits

- The API's latency is the floor for a page nobody has viewed in the last hour.
- Character images load straight from the API's CDN, under its own 90-day cache policy and
  rate limit. A fresh list page requests up to 20 images.
- `php artisan serve` (also used in the Docker image) is a development server. Production would
  run php-fpm behind nginx, with `APP_DEBUG=false` and a shared cache such as Redis
  (one `.env` change: `CACHE_STORE=redis`).

## Where AI helped, and where I overrode it

I used an AI assistant throughout, in the order plan, test list, tests, code, docs, and the commit
history shows that order. The plan and the test list were agreed before any code; the AI drafted
test code and application code from them and I reviewed every file. I changed its first design
twice: the API client was going to be raw cURL behind a custom interface, and I replaced that
with Laravel's own HTTP client because the brief's rule is about who writes the communication,
not which library carries it; and `NotFoundException` was first a kind of `ApiException`, which
would have shown "API unavailable" for a missing character, so I split it out. One bug reached
the browser that the suite had not caught (cached objects refused by Laravel 13's unserialize
allow-list); it was reproduced in the suite before being fixed, and the test cache now serializes
so that class of bug cannot return.
