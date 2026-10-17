# Test cases

All tests run without network. The API client sends requests through Laravel's `Http` client, and
every test starts with `Http::fake([...])`, which answers each URL pattern with a canned response and
records what was sent. Feature tests use the same fake, so the real client code runs end to end.
One optional live smoke test runs only when `RM_LIVE_TEST=1`.

## Unit: ApiClient (tests/Unit/RickAndMorty/ApiClientTest.php)

| ID | Given | Then |
|---|---|---|
| T1 | `characters(page: 2)` | request URL is `https://rickandmortyapi.com/api/character?page=2` |
| T1b | any request | sends `Accept: application/json` and a `User-Agent` |
| T2 | `characters(1, ['name' => 'rick morty', 'status' => 'alive', 'gender' => '', 'species' => null])` | query is `page, name, status` only; empty values dropped |
| T3 | page and filter combinations (data provider) | query contains exactly the given keys; unknown keys are not forwarded; whitespace-only values dropped |
| T4 | 200 with a valid list body | `CharacterPage` with 20 `Character` objects, `page`, `pages`, `count`; fields mapped; episode URLs become ids |
| T4b | page 42 of 42 | `hasPrevious()` true, `hasNext()` false |
| T5 | 404 with `{"error":"There is nothing here"}` | empty `CharacterPage`, no exception |
| T6 | 429 | throws `RateLimitedException` |
| T7 | 500, 503, 403, non-JSON body, JSON missing `results` or `info` (data provider) | throws `ApiException` |
| T8 | connection failure (timeout) | throws `ApiException` with `ConnectionException` as previous |
| T9 | `episodes([1, 2, 3])` | one request to `/episode/1,2,3`; three `Episode` objects with id, name, code, airDate |
| T9b | `episodes([7])` and API returns one object | still returns a list of one |
| T9c | `episodes([])` | returns `[]`, no request sent |
| T10 | `character(1)` | request to `/character/1`; `Character` mapped |
| T11 | `character(999999)` and API returns 404 | throws `NotFoundException` |

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
| D2 | `GET /characters/999999` (API 404) | 404 page naming the character id |
| D3 | `GET /characters/abc` | 404, `Http::assertNothingSent` |
| D4 | Character with 51 episodes | one episode request, with all 51 ids |

## Feature: errors and cache (tests/Feature/ResilienceTest.php)

| ID | Given | Then |
|---|---|---|
| E1 | Fake throws `ConnectionException` | 503 page "not responding", no cURL text leaks |
| E2 | Fake returns 429 | 503 page "Too many requests" |
| E3 | Fake returns 500, 502, or non-JSON (data provider) | 503 page on list and detail |
| E3b | Error page | links back to the list |
| E4 | Same list URL requested twice | `Http::assertSentCount(1)` (cache hit) |
| E4b | Two different pages | two calls (cached separately) |
| E4c | Detail page twice | two calls, not four |
| E5 | Same filters in different URL order, with or without empty params | one call (key normalisation) |
| E5b | First call fails, second succeeds | failure is not cached; second call reaches the API |

## Live smoke (tests/Live/LiveApiTest.php, skipped unless `RM_LIVE_TEST=1`)

| ID | Then |
|---|---|
| V1 | Page 1 returns 20 characters and `pages` > 1 against the real API |
