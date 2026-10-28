# Plan: warm the image cache from the command line

Status: **done** (2026-10-28). Kept as the record of how the work was planned. Companion file: [test-cases.md](test-cases.md).

## Why

Images are served through `/avatars/{id}` and cached for 90 days, but the first visit to a page
nobody has opened still fetches up to 20 images from the API within a few seconds. On 2026-10-28
the API answered 429 to most of such a burst (80 rate-limit errors in the log, no other kind), so
half the cards on a cold page showed broken images. The fix is to take the cold path away from the
visitor: fetch every image once, slowly, from the command line.

## Goal

`php artisan avatars:warm` fills the same cache entries the controller reads, two images a second,
skipping what is already there, and stops on the first 429.

## Exit criteria

- [x] `php artisan avatars:warm` on an empty cache fetches every image the API has (count from the API) and prints fetched / already cached / failed counts.
- [x] A second run makes no API request for images and finishes at once.
- [x] A 429 stops the run with a non-zero exit code and a line saying where it stopped.
- [x] A 404 or a 5xx on one image is counted and the run continues.
- [x] `php artisan test` green; Pint clean; README and `docs/reference` updated.

## Decisions

| Decision | Choice | One-line why |
|---|---|---|
| Which ids | 1 to `info.count`, read from page 1 of the character list through the existing client and cache | Ids are contiguous; a hard-coded 826 would break silently when the API grows |
| Already cached | `Cache::has` before fetching; a hit is counted and skipped, no pause | A re-run after a restart or a partial run costs nothing |
| Pace | Half a second between real fetches, a constant in the command (started at one second, halved after the first full run showed no 429) | The log shows the limiter trips on bursts, not on a slow trickle |
| 429 | Stop, print the id reached, exit code 1 | Carrying on during a ban extends it; the next run starts where this one stopped, because of the skip rule |
| 404 | Counted as failed, loop continues | No image for that id; nothing to store |
| 5xx, timeout, bad body | Counted as failed, loop continues | One bad answer should not stop the other 800 |
| Sleeping | Laravel's `Sleep` class, not `sleep()` | Tests fake it and assert on it instead of waiting |
| Where it runs | By hand, documented in the README as an optional step | No change to Docker; the scheduler needs a process this app does not have |
| Cache key | The same `rickandmorty:avatar:{id}` the controller uses, same lifetime from config | The command and the route can never disagree about where an image lives |
| Exit code | 0 when nothing failed, 1 otherwise | A cron or a person can tell at a glance |

## Build steps

| # | Step | Visible result when done | Exit criteria |
|---|---|---|---|
| 1 | This plan and the test list | `git log` shows the plan before any code | Committed |
| 2a | Failing tests W1 to W6 | `php artisan test` shows six failures | Committed red |
| 2b | `app/Console/Commands/WarmAvatarCache.php` | `php artisan avatars:warm` runs and the next cold page has no broken images | W1 to W6 green; committed |
| 3 | README, `docs/reference/architecture.md`, `testing.md` | Docs name the command | Committed |

## The command, top to bottom

```
php artisan avatars:warm
  |
  v
ApiClient::characters(page: 1) via the list cache ..... info.count, the highest id
  |
  v
for id = 1 .. count
    Cache::has("rickandmorty:avatar:{id}") ? skip (count it)
    else ApiClient::avatar(id)
         ok          -> Cache::put(key, bytes, avatar_cache_seconds); Sleep 0.5 s
         NotFound    -> failed++, continue
         RateLimited -> print "stopped at id N", exit 1
         ApiException-> failed++, continue
  |
  v
"Fetched 826, already cached 0, failed 0." exit 0 or 1
```
