# Testing

```bash
php artisan test                                   # 69 tests (1 skipped), no network
RM_LIVE_TEST=1 php artisan test --filter LiveApiTest   # one test against the real API
vendor/bin/pint --test                             # code style
```

## Layers

| Suite | File | What it proves |
|---|---|---|
| Unit | `tests/Unit/RickAndMorty/ApiClientTest.php` | The client builds the right URL, maps good answers, and turns every bad answer (404, 429, 5xx, non-JSON, timeout) into the right result or exception. |
| Feature | `tests/Feature/CharacterListTest.php` | List page, pagination, invalid page values. |
| Feature | `tests/Feature/CharacterSearchTest.php` | Filters forwarded, invalid values rejected, output escaped, page links keep validated filters only. |
| Feature | `tests/Feature/CharacterShowTest.php` | Detail page, episodes in one call, 404 for unknown or non-numeric ids. |
| Feature | `tests/Feature/ResilienceTest.php` | Friendly 503 pages; cache hits, cache keys, failures not cached. |
| Live | `tests/Feature/LiveApiTest.php` | Skipped unless `RM_LIVE_TEST=1`. |

## How the API is faked

Every test starts with `Http::fake([...])`, which answers URL patterns with canned responses and
records what was sent. `tests/Support/RickAndMortyFixtures.php` holds the canned bodies in the
exact shape the API returns, shared by all suites. Feature tests run the real client end to end;
only the network is replaced.

## IDs

Each test method name carries an ID (T1, L3, S4, D2, E5b ...) that matches the row in
`docs/tasks/2026-10-17_build-encyclopedia/test-cases.md`, the list that was written and reviewed
before the tests were coded. Tests were committed red before the code that makes them green.

## The test cache serializes on purpose

`config/cache.php` sets the array store to serialize, unlike Laravel's default. The file store
used at runtime serializes, and Laravel 13 refuses to restore classes not on its allow-list. With
the default setting the suite could not see that; now it can.
