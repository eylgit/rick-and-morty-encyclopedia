# Architecture

One request, top to bottom:

```
GET /characters?name=rick&status=alive&page=2
  |
  v
routes/web.php ............................ three routes: redirect /, list, detail (digits only)
  |
  v
app/Http/Requests/CharacterListRequest .... validates page + four filters; bad input -> 302 to /characters
  |
  v
app/Http/Controllers/CharacterController . Cache::remember(key from validated input, 1 h, fn => client call)
  |                                          |
  |                                          v
  |                                   app/RickAndMorty/ApiClient ...... Http::get with timeouts; 404/429/5xx/bad JSON
  |                                          |                           mapped to CharacterPage / exceptions
  v                                          v
resources/views/characters/*.blade.php ... Blade + Bootstrap 5 (CDN); paginator draws links with validated filters
  |
  v
bootstrap/app.php ......................... any ApiException escaping the controller -> errors/api-unavailable (503)
```

Images take a shorter path. The pages point every `<img>` at `/avatars/{id}` on this app:

```
GET /avatars/1
  |
  v
routes/web.php ............................ digits only
  |
  v
app/Http/Controllers/AvatarController .... Cache::remember("avatar:1", 90 d, fn => ApiClient::avatar(1))
  |                                          404 -> our 404; Content-Type not image/* -> ApiException
  v
response ................................. image/jpeg, Cache-Control public max-age=7776000 immutable, ETag;
                                           304 when If-None-Match matches, so a reload never reaches the API
```

## Folders that matter

| Folder | Role |
|---|---|
| `app/RickAndMorty/` | Model layer. `ApiClient` plus three read-only data classes and three exceptions. Imports only Laravel's `Http` client. |
| `app/Http/Requests/` | Validation. The request class also owns the allowed values for status and gender, which the view uses to draw dropdowns. |
| `app/Console/Commands/` | `WarmAvatarCache`: `php artisan avatars:warm` fills the image cache one id a second, skipping cached ids, stopping on 429. |
| `app/Http/Controllers/` | `CharacterController`: two actions, a private cache helper, a private paginator helper. `AvatarController`: one action that caches and serves image bytes. |
| `resources/views/` | `layouts/app` (Bootstrap shell), `characters/index`, `characters/show`, `errors/404`, `errors/api-unavailable`. |
| `config/rickandmorty.php` | Base URL, timeout, cache lifetimes (data 1 h, images 90 d). `config/cache.php` lists the three data classes the cache may restore. |
| `tests/` | `Unit/RickAndMorty` for the client, `Feature` for pages, `Support` for shared canned API bodies. |

Everything else is the unmodified Laravel 13 skeleton, minus the Node build files.

## Data flow for the detail page

`/characters/1` costs two API calls on a cold cache: the character, then all its episodes in one
request (`/episode/1,2,...`). Both answers are cached under their own keys; episode lists shared
by several characters share a cache entry.

## Data flow for an image

`/avatars/1` costs one API call on a cold cache and none for 90 days after. The browser is told
the same lifetime, so ordinary navigation sends no request at all, and a hard reload sends a
conditional request that this app answers with 304 from the cache. Before this route existed the
`<img>` tags pointed at the API's CDN, and a few dozen hard reloads from one browser earned a 429
and broken images, because the CDN counts every revalidation.

The cold path still fetches up to 20 images in a burst for a page nobody has opened, and the API
has answered 429 to such bursts (80 in one afternoon's log, no other failure kind). The warm-up
command takes that path away: it walks ids 1 to `info.count`, fetches the missing ones into the
same cache entries at two requests a second, and exits non-zero on the first 429 so a re-run can
continue from that id.
