# Test cases: warm the image cache

Same rules as before: no network, `Http::fake([...])`, canned bodies from the shared trait.
`Sleep::fake()` replaces real sleeping; the tests assert on what would have been slept.

## Feature: the command (tests/Feature/WarmAvatarCacheTest.php)

| ID | Given | Then |
|---|---|---|
| W1 | empty cache, API says `count` is 3, three images answer | three image requests, one per id; the cache holds each image under `rickandmorty:avatar:{id}`; output says fetched 3, already cached 0, failed 0; exit code 0 |
| W2 | ids 1 and 3 already in the cache, count 3 | one request, for id 2; output says fetched 1, already cached 2; exit code 0 |
| W3 | id 2 answers 404, count 3 | ids 1 and 3 are cached; failed 1; exit code 1 |
| W4 | id 2 answers 429, count 3 | id 1 is cached, id 3 is never requested; output names id 2; exit code 1 |
| W5 | id 2 answers 500, count 3 | ids 1 and 3 are cached; failed 1; exit code 1 |
| W6 | W1 setup | slept one second exactly three times (once after each real fetch); with W2 setup, slept once |
