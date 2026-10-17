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

## Folders that matter

| Folder | Role |
|---|---|
| `app/RickAndMorty/` | Model layer. `ApiClient` plus three read-only data classes and three exceptions. Imports only Laravel's `Http` client. |
| `app/Http/Requests/` | Validation. The request class also owns the allowed values for status and gender, which the view uses to draw dropdowns. |
| `app/Http/Controllers/` | One controller, two actions, one private cache helper, one private paginator helper. |
| `resources/views/` | `layouts/app` (Bootstrap shell), `characters/index`, `characters/show`, `errors/404`, `errors/api-unavailable`. |
| `config/rickandmorty.php` | Base URL, timeout, cache lifetime. `config/cache.php` lists the three data classes the cache may restore. |
| `tests/` | `Unit/RickAndMorty` for the client, `Feature` for pages, `Support` for shared canned API bodies. |

Everything else is the unmodified Laravel 13 skeleton, minus the Node build files.

## Data flow for the detail page

`/characters/1` costs two API calls on a cold cache: the character, then all its episodes in one
request (`/episode/1,2,...`). Both answers are cached under their own keys; episode lists shared
by several characters share a cache entry.
