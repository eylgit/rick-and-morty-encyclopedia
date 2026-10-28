# Decisions and their reasons

| Decision | Chosen | Alternatives considered | Why |
|---|---|---|---|
| Framework | Laravel 13, thin use (routing, validation, Blade, cache, tests) | Plain PHP with a hand-rolled MVC; Slim | Gives validation, escaping, config and a test harness for free, and is the most widely used PHP framework, so the layout is familiar to any PHP reader. Unused parts (database, auth, queues) are left untouched; only the Node/Vite build files were removed. |
| HTTP layer | Laravel's `Http` client, hand-written calls | Raw cURL behind a custom interface | Any generic HTTP library is fine as long as the API calls themselves are hand-written; Laravel's client keeps them short, and `Http::fake()` gives network-free tests. |
| Front end | Blade + Bootstrap 5 via CDN, no JavaScript | Vue + Inertia, React | Functionality first; no Node build step to install or run. State lives in the URL. |
| Page model | Multi-page | Single-page app | Every click replaces the whole result set and must be bookmarkable; a server round trip is the natural unit. |
| Pagination | API pages (20 fixed); `LengthAwarePaginator` fed with the API's count only draws links | Hand-written prev/next | Standard markup, keeps validated filters on every link. |
| Cache | File store, 1 hour, key = validated page + filters in fixed order | No cache; cache per entity | API data is immutable for 90 days and the API is rate limited (429 observed). Failures are never cached. Redis is one `.env` change. |
| Database | None | Copy characters into MySQL | A copy adds sync and setup; the API already pages and filters. |
| 404 semantics | List 404 = empty page; single 404 = `NotFoundException` -> our 404 page | Treat both as errors | Verified against the live API: a search with no match is a 404 with "There is nothing here". |
| Errors | One `render` rule in `bootstrap/app.php` turns `ApiException` into a 503 page; 429 gets its own wording; no retry | try/catch in each action; retry on 429 | One place, both actions covered; retrying a rate limit makes it worse. |
| Input | `FormRequest` with whitelists; bad input redirects to `/characters`; page links append only validated filters | 422 error page | Friendlier for a mistyped bookmark; nothing unvalidated is ever forwarded or echoed. |
| Cache safety | `config/cache.php` allow-lists the three data classes; the test cache serializes | Cache arrays instead of objects | Laravel 13 refuses to restore any class not listed (gadget-chain defence). Serializing in tests caught the gap. |
| Images | Served through the app at `/avatars/{id}`; bytes in the same cache for 90 days; `immutable` plus `ETag`, 304 on match | Leave `<img>` pointing at the API's CDN; mirror all 826 files to disk at build time | The CDN counts every revalidation, so a hard reload of a 20-card page spends 20 of the rate limit and eventually breaks the images. The id is the only input, so the route cannot be used as an open proxy. Mirroring is the production step, documented, not shipped. |
| Docker | `php:8.4-cli-alpine` + `artisan serve`, one compose service | None; php-fpm + nginx | Runs with nothing but Docker installed; the production shape (php-fpm + nginx) is documented, not shipped. |
