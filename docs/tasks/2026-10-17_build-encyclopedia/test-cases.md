# Test cases

All tests run without network. The API client sends requests through Laravel's `Http` client, and
every test starts with `Http::fake([...])`, which answers each URL pattern with a canned response and
records what was sent. Feature tests use the same fake, so the real client code runs end to end.
One optional live smoke test runs only when `RM_LIVE_TEST=1`.

## Unit: ApiClient (tests/Unit/ApiClientTest.php)

| ID | Given | Then |
|---|---|---|
| T1 | `characters(page: 2)` | `Http::assertSent` sees `https://rickandmortyapi.com/api/character?page=2` |
| T2 | `characters(name: "rick morty", status: "alive")` | query is URL-encoded; empty filters are omitted |
| T3 | Any page and filter combination (data provider) | URL contains exactly the given params, nothing else |
| T4 | 200 with a valid list body | returns `CharacterPage` with 20 `Character` objects and `pages` count |
| T5 | 404 with `{"error":"There is nothing here"}` | returns empty `CharacterPage`, no exception |
| T6 | 429 | throws `RateLimitedException` |
| T7 | 500, or body that is not JSON, or JSON missing `results` | throws `ApiException` |
| T8 | fake throws a `ConnectionException` (timeout) | throws `ApiException` with the cause |
| T9 | `episodes([1, 2, 3])` | one request to `/episode/1,2,3`; `episodes([1])` still returns a list |

## Feature: list page (tests/Feature/CharacterListTest.php)

| ID | Request | Then |
|---|---|---|
| L1 | `GET /characters` | 200, 20 names in HTML, "Page 1 of N" |
| L2 | `GET /characters?page=3` | `Http::assertSent` sees `page=3` |
| L3 | `GET /characters?page=0` and `?page=abc` | 302 back to page 1 or 422, never a 500 |
| L4 | `GET /characters?page=999` (API 404) | 200 with "No characters found" |
| L5 | `GET /` | redirects to `/characters` |
| L6 | Page links on page 2 | contain `page=1` and `page=3` |

## Feature: search and filter (tests/Feature/CharacterSearchTest.php)

| ID | Request | Then |
|---|---|---|
| S1 | `?name=rick` | request sent with `name=rick`; input keeps the value "rick" |
| S2 | `?status=alive&gender=female` | both forwarded |
| S3 | `?status=zombie` | 422 or redirect, never forwarded |
| S4 | `?name=<script>alert(1)</script>` | HTML contains the escaped form, not the raw tag |
| S5 | Page links while searching | keep `name=rick&status=alive` |

## Feature: detail page (tests/Feature/CharacterShowTest.php)

| ID | Request | Then |
|---|---|---|
| D1 | `GET /characters/1` | 200 with image URL, name, species, origin, episode names |
| D2 | `GET /characters/999999` (API 404) | 404 page |
| D3 | `GET /characters/abc` | 404, `Http::assertNothingSent` |
| D4 | Character with 51 episodes | one episode request, with all 51 ids |

## Feature: errors and cache (tests/Feature/ResilienceTest.php)

| ID | Given | Then |
|---|---|---|
| E1 | Fake throws `ConnectionException` | 503 page "could not reach the API", not a 500 |
| E2 | Fake returns 429 | 503 page "API is busy, try again shortly" |
| E3 | Fake returns 500 | 503 page |
| E4 | Same list URL requested twice | `Http::assertSentCount(1)` (cache hit) |
| E5 | Two URLs differing only in param order | `Http::assertSentCount(1)` (key normalisation) |

## Live smoke (tests/Live/LiveApiTest.php, skipped unless `RM_LIVE_TEST=1`)

| ID | Then |
|---|---|
| V1 | Page 1 returns 20 characters and `pages` > 1 against the real API |
