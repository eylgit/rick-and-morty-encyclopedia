<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\RickAndMortyFixtures;
use Tests\TestCase;

/** Errors and cache. IDs E1..E5 match docs/tasks/.../test-cases.md. */
final class ResilienceTest extends TestCase
{
    use RickAndMortyFixtures;

    // ---------------------------------------------------------------- errors

    public function test_e1_connection_failure_shows_a_friendly_503_not_a_500(): void
    {
        Http::fake([self::API.'/*' => Http::failedConnection('cURL error 28: timed out')]);

        $response = $this->get('/characters');

        $response->assertStatus(503);
        $response->assertSee('not responding');
        $response->assertDontSee('cURL error');
    }

    public function test_e2_rate_limit_shows_a_try_again_shortly_page(): void
    {
        Http::fake([self::API.'/*' => Http::response('', 429)]);

        $response = $this->get('/characters?name=rick');

        $response->assertStatus(503);
        $response->assertSee('Too many requests');
    }

    #[DataProvider('brokenAnswers')]
    public function test_e3_server_errors_and_bad_bodies_show_the_503_page(mixed $body, int $status): void
    {
        Http::fake([self::API.'/*' => Http::response($body, $status)]);

        $this->get('/characters')->assertStatus(503)->assertSee('not responding');
        $this->get('/characters/1')->assertStatus(503)->assertSee('not responding');
    }

    public static function brokenAnswers(): array
    {
        return [
            'HTTP 500' => ['', 500],
            'HTTP 502' => ['<html>bad gateway</html>', 502],
            'not JSON' => ['<html>oops</html>', 200],
        ];
    }

    public function test_e3b_error_pages_offer_a_way_back(): void
    {
        Http::fake([self::API.'/*' => Http::response('', 500)]);

        $this->get('/characters/1')->assertSee('href="'.route('characters.index').'"', false);
    }

    // ---------------------------------------------------------------- cache

    public function test_e4_the_same_list_page_twice_calls_the_api_once(): void
    {
        Http::fake([self::API.'/character*' => Http::response($this->pageBody())]);

        $this->get('/characters?page=2')->assertStatus(200);
        $this->get('/characters?page=2')->assertStatus(200);

        Http::assertSentCount(1);
    }

    public function test_e4b_different_pages_are_cached_separately(): void
    {
        Http::fake([self::API.'/character*' => Http::response($this->pageBody())]);

        $this->get('/characters?page=1');
        $this->get('/characters?page=2');

        Http::assertSentCount(2);
    }

    public function test_e4c_the_detail_page_twice_calls_the_api_twice_not_four_times(): void
    {
        Http::fake([
            self::API.'/character/1' => Http::response($this->characterData(1, 'Rick Sanchez')),
            self::API.'/episode/*' => Http::response([$this->episodeData(1, 'Pilot', 'S01E01'), $this->episodeData(2, 'Lawnmower Dog', 'S01E02')]),
        ]);

        $this->get('/characters/1')->assertStatus(200);
        $this->get('/characters/1')->assertStatus(200);

        Http::assertSentCount(2);
    }

    public function test_e5_filter_order_in_the_url_does_not_change_the_cache_key(): void
    {
        Http::fake([self::API.'/character*' => Http::response($this->pageBody(results: 3, pages: 1, count: 3))]);

        $this->get('/characters?status=alive&name=rick');
        $this->get('/characters?name=rick&status=alive');
        $this->get('/characters?name=rick&status=alive&gender=&species=');

        Http::assertSentCount(1);
    }

    public function test_e5b_a_failed_request_is_not_cached(): void
    {
        // First call fails, second succeeds: the failure must not be remembered.
        Http::fake([self::API.'/character*' => Http::sequence()
            ->push('', 500)
            ->push($this->pageBody())]);

        $this->get('/characters')->assertStatus(503);
        $this->get('/characters')->assertStatus(200);

        Http::assertSentCount(2);
    }
}
