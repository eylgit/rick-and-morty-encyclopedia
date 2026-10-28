# Test cases: serve character images through the app

Same rules as the first task: no network, `Http::fake([...])` in every test, canned bodies from
`tests/Support/RickAndMortyFixtures.php`. Image bodies are a short fake string sent with
`Content-Type: image/jpeg`; the client does not inspect the bytes.

## Unit: ApiClient::avatar() (tests/Unit/RickAndMorty/ApiClientTest.php)

| ID | Given | Then |
|---|---|---|
| A1 | `avatar(1)` | one request to `https://rickandmortyapi.com/api/character/avatar/1.jpeg` |
| A2 | 200 with `Content-Type: image/jpeg` | returns the body string unchanged |
| A3 | 404 | throws `NotFoundException` |
| A4 | 429 | throws `RateLimitedException` |
| A5 | 500, connection failure, 200 with `Content-Type: text/html` (data provider) | throws `ApiException` |

## Feature: the avatar route (tests/Feature/AvatarTest.php)

| ID | Given | Then |
|---|---|---|
| P1 | `GET /avatars/1`, API answers an image | 200, `Content-Type: image/jpeg`, body is the image, `Cache-Control` is `public, max-age=7776000, immutable`, an `ETag` is present |
| P2 | `GET /avatars/1` twice | API called once (`Http::assertSentCount(1)`); second body identical |
| P3 | `GET /avatars/1`, then again with `If-None-Match` set to the `ETag` from the first | 304, empty body, API still called once |
| P4 | API answers 429 | 503 page; a following request calls the API again (nothing cached) |
| P5 | API answers 404 | 404 page |
| P6 | `GET /avatars/abc` | 404, no API request |
| P7 | `GET /characters` and `GET /characters/1` | every `<img src>` starts with `/avatars/`; no `rickandmortyapi.com` in any `src` |
