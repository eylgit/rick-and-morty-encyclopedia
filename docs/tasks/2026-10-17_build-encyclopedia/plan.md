# Plan: build the Rick and Morty Encyclopedia

Status: **draft v1, not started**. Companion file: [test-cases.md](test-cases.md).

## Goal

A server-rendered Laravel app that lists Rick and Morty characters with pagination, search and
filters, and shows a detail page per character. Runnable from a clean clone with one command.

## Exit criteria for the whole task

- [ ] `/characters` lists characters, 20 per page, with working page links.
- [ ] Search by name and filters (status, gender, species) work and survive a browser refresh.
- [ ] `/characters/{id}` shows image, name, species, origin and episode names.
- [ ] Empty results, invalid input, API timeout, API 5xx and API 429 each render a friendly page, never a 500.
- [ ] `php artisan test` is green. CI workflow is green.
- [ ] `docker compose up` on a clean clone serves the app on `http://localhost:8000`.
- [ ] README has: run steps, decisions, what was left out, known limits, AI usage.

## Decisions (short form; detail in `docs/reference/decisions.md` once built)

| Decision | Choice | One-line why |
|---|---|---|
| Framework | Laravel 13, used thinly | Team's stack; gives routing, validation, escaping, config, tests for free |
| Front end | Blade + Bootstrap 5 via CDN | Brief says front-end framework optional; no Node build step for the reviewer |
| Page model | Multi-page, full page loads | State lives in the URL; bookmark, back, refresh and share work with no JS |
| API client | Hand-written, cURL, behind a one-method `HttpFetcher` interface | Testable without network; no framework dependency; could become a Composer package |
| Cache | Laravel file cache, key = normalised API URL, TTL 10 min | Answers rate limit; never returns wrong data because the key contains every parameter |
| Database | None | Data lives in the API; a copy would add sync and setup with no benefit for this scope |
| Rate limit | Cache first; on HTTP 429 show "API busy" page; no automatic retry on 429 | Retrying a rate limit makes it worse |

## Build steps

Each step: tests first, then code, then one commit. Stop after each step for review.

| # | Step | Visible result when done | Exit criteria |
|---|---|---|---|
| 0 | Tooling: install PHP 8.3+ and Composer | `php -v` prints 8.3 or higher | Done 2026-10-17: PHP 8.5.11, Composer 2.10.3 |
| 1 | Seed repo from the assessment repo, keep its commit as the first commit | `git log` shows the original commit then ours | Original README moved to `docs/ASSESSMENT.md` |
| 2 | Laravel skeleton, Node files removed | `php artisan serve` shows the Laravel welcome page | Commit "Scaffold Laravel 13, drop front-end build" |
| 3 | API client (written by the candidate): `HttpFetcher`, `CurlFetcher`, `ApiClient`, `Character`, `CharacterPage`, exceptions | `php artisan test --filter ApiClient` green | Unit tests T1 to T9 pass |
| 4 | Character list page: route, controller, request validation, Blade view, pagination | Browser shows 20 characters and page links | Feature tests L1 to L6 pass |
| 5 | Search and filters; pagination keeps the query | Search "rick" with status "alive" works; page 2 keeps the filter | Tests S1 to S5 pass |
| 6 | Detail page with episodes in one batch call | `/characters/1` shows Rick with episode names | Tests D1 to D4 pass |
| 7 | Cache and error pages (timeout, 5xx, 429, empty) | Second load of a page makes no API call | Tests E1 to E5 pass |
| 8 | Dockerfile, compose, CI workflow, README, `docs/reference` | Clean clone runs with `docker compose up` | Whole-task exit criteria ticked |
| 9 | Review pass and private repo with collaborators invited | Reviewer can open the repo | Checklist in README verified |

## Wireframes

```
/characters?page=2&name=rick&status=alive
+--------------------------------------------------------------+
| Rick and Morty Encyclopedia                                  |
+--------------------------------------------------------------+
| [ name: rick      ] [status: alive v] [gender: any v] [Search]|
+--------------------------------------------------------------+
| [img] Rick Sanchez     Human   Alive    -> details            |
| [img] Rick D. Sanchez  Human   Alive    -> details            |
| ... 20 rows ...                                               |
+--------------------------------------------------------------+
| < Prev   Page 2 of 4   Next >                                 |
+--------------------------------------------------------------+

/characters/1
+--------------------------------------------------------------+
| < Back to list                                                |
| [ big image ]   Rick Sanchez                                  |
|                 Species: Human   Status: Alive   Gender: Male |
|                 Origin: Earth (C-137)                         |
|                 Episodes (51):                                |
|                   S01E01 Pilot                                |
|                   S01E02 Lawnmower Dog ...                    |
+--------------------------------------------------------------+
```

## Deliberately left out

Authentication, database, Vue/Inertia, episode and location browsing, JavaScript pagination,
retry on rate limit, static analysis. Each is noted in the README with the reason.
