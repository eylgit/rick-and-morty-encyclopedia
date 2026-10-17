# The Rick and Morty API as observed

Verified against the live API on 2026-10-17 with ten single requests. Documentation:
https://rickandmortyapi.com/documentation. Base URL: `https://rickandmortyapi.com/api`.

## Endpoints we use

| Call | Returns |
|---|---|
| `GET /character?page=N&name=&status=&species=&type=&gender=` | `{"info": {"count", "pages", "next", "prev"}, "results": [character, ...]}`, 20 per page, 826 characters over 42 pages |
| `GET /character/{id}` | one character object |
| `GET /episode/{id},{id},...` | a **list** of episode objects when several ids are given, a **single object** when one id is given |

Character fields: `id, name, status, species, type, gender, origin{name,url}, location{name,url}, image, episode[url...], url, created`.
Documented filter values: `status` = alive, dead, unknown; `gender` = female, male, genderless, unknown. `name`, `species` and `type` are free text.

## Behaviour that shaped the client

| Request | Observed | Our reading |
|---|---|---|
| Search with no match (`?name=zzzznobody`) | `404 {"error":"There is nothing here"}` | Empty page, not an error |
| Invalid filter value (`?status=zombie`) | `404 {"error":"There is nothing here"}` | Same: empty page |
| Page beyond the last (`?page=9999`) | `404 {"error":"There is nothing here"}` | Same: empty page |
| `?page=0` or `?page=abc` | `200`, page 1 | API is lenient; we still validate on our side |
| Unknown character id (`/character/999999`) | `404 {"error":"Character not found"}` | Not found error |
| One episode id (`/episode/1`) | single object, not a list | Client normalises to a list |

## Rate limiting and caching

- The API is known to be rate limited, but the documentation page states no figure and the
  responses carry no rate-limit headers.
- Every response is served through Cloudflare with `cache-control: public, max-age=7776000,
  immutable` (90 days), and common pages come back as cache hits. The data set is effectively
  static.
- The limit is real: during ordinary browsing on 2026-10-17 (a few dozen requests within a few
  minutes from one IP) the API answered `429` for the list endpoint. It cleared within a minute.
  No `Retry-After` header could be captured.
- Consequence: a server-side cache keeps our request volume low, and an HTTP 429 is shown to the
  visitor as "try again shortly" rather than retried, since retrying a rate limit makes it worse.
