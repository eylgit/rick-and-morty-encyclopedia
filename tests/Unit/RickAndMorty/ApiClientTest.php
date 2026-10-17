<?php

declare(strict_types=1);

namespace Tests\Unit\RickAndMorty;

use App\RickAndMorty\ApiClient;
use App\RickAndMorty\ApiException;
use App\RickAndMorty\Character;
use App\RickAndMorty\CharacterPage;
use App\RickAndMorty\Episode;
use App\RickAndMorty\NotFoundException;
use App\RickAndMorty\RateLimitedException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\RickAndMortyFixtures;
use Tests\TestCase;

/**
 * Tests for the hand-written API client. IDs (T1..T11) match docs/tasks/.../test-cases.md.
 * No test here touches the network: Http::fake() answers every request.
 */
final class ApiClientTest extends TestCase
{
    use RickAndMortyFixtures;

    private const BASE = self::API;

    // ---------------------------------------------------------------- T1-T3: what we send

    public function test_t1_page_is_sent_as_a_query_parameter(): void
    {
        Http::fake([self::BASE.'/character*' => Http::response($this->pageBody())]);

        $this->client()->characters(page: 2);

        Http::assertSent(fn (Request $r) => $r->url() === self::BASE.'/character?page=2');
    }

    public function test_t1b_requests_ask_for_json_and_identify_the_app(): void
    {
        Http::fake([self::BASE.'/character*' => Http::response($this->pageBody())]);

        $this->client()->characters();

        Http::assertSent(fn (Request $r) => $r->hasHeader('Accept', 'application/json')
            && $r->hasHeader('User-Agent'));
    }

    public function test_t2_filters_are_sent_and_empty_filters_are_dropped(): void
    {
        Http::fake([self::BASE.'/character*' => Http::response($this->pageBody())]);

        $this->client()->characters(1, ['name' => 'rick morty', 'status' => 'alive', 'gender' => '', 'species' => null]);

        Http::assertSent(fn (Request $r) => $r->data() === ['page' => 1, 'name' => 'rick morty', 'status' => 'alive']);
    }

    #[DataProvider('pageAndFilterCombinations')]
    public function test_t3_query_contains_exactly_the_given_parameters(int $page, array $filters, array $expected): void
    {
        Http::fake([self::BASE.'/character*' => Http::response($this->pageBody())]);

        $this->client()->characters($page, $filters);

        Http::assertSent(fn (Request $r) => $r->data() === $expected);
    }

    public static function pageAndFilterCombinations(): array
    {
        return [
            'page only' => [1, [], ['page' => 1]],
            'high page' => [42, [], ['page' => 42]],
            'name only' => [1, ['name' => 'morty'], ['page' => 1, 'name' => 'morty']],
            'all filters' => [
                3,
                ['name' => 'rick', 'status' => 'dead', 'species' => 'human', 'type' => 'clone', 'gender' => 'male'],
                ['page' => 3, 'name' => 'rick', 'status' => 'dead', 'species' => 'human', 'type' => 'clone', 'gender' => 'male'],
            ],
            'unknown key is not forwarded' => [1, ['name' => 'rick', 'evil' => 'yes'], ['page' => 1, 'name' => 'rick']],
            'whitespace-only value is dropped' => [1, ['name' => '   '], ['page' => 1]],
        ];
    }

    // ---------------------------------------------------------------- T4-T5: a good answer

    public function test_t4_successful_list_is_mapped_to_a_character_page(): void
    {
        Http::fake([self::BASE.'/character*' => Http::response($this->pageBody(results: 20, pages: 42, count: 826))]);

        $page = $this->client()->characters(page: 2);

        $this->assertInstanceOf(CharacterPage::class, $page);
        $this->assertCount(20, $page->characters);
        $this->assertSame(2, $page->page);
        $this->assertSame(42, $page->pages);
        $this->assertSame(826, $page->count);
        $this->assertTrue($page->hasPrevious());
        $this->assertTrue($page->hasNext());

        $first = $page->characters[0];
        $this->assertInstanceOf(Character::class, $first);
        $this->assertSame(1, $first->id);
        $this->assertSame('Character 1', $first->name);
        $this->assertSame('Alive', $first->status);
        $this->assertSame('Human', $first->species);
        $this->assertSame('Male', $first->gender);
        $this->assertSame('Earth (C-137)', $first->origin);
        $this->assertSame('Citadel of Ricks', $first->location);
        $this->assertSame('https://rickandmortyapi.com/api/character/avatar/1.jpeg', $first->image);
        $this->assertSame([1, 2], $first->episodeIds);
    }

    public function test_t4b_last_page_has_no_next(): void
    {
        Http::fake([self::BASE.'/character*' => Http::response($this->pageBody(results: 6, pages: 42, count: 826))]);

        $page = $this->client()->characters(page: 42);

        $this->assertTrue($page->hasPrevious());
        $this->assertFalse($page->hasNext());
    }

    public function test_t5_404_on_the_list_means_no_results_not_an_error(): void
    {
        Http::fake([self::BASE.'/character*' => Http::response($this->nothingHere(), 404)]);

        $page = $this->client()->characters(1, ['name' => 'nobody']);

        $this->assertTrue($page->isEmpty());
        $this->assertSame(0, $page->pages);
        $this->assertSame(0, $page->count);
        $this->assertFalse($page->hasNext());
        $this->assertFalse($page->hasPrevious());
    }

    // ---------------------------------------------------------------- T6-T8: bad answers

    public function test_t6_429_throws_rate_limited(): void
    {
        Http::fake([self::BASE.'/*' => Http::response('', 429)]);

        $this->expectException(RateLimitedException::class);

        $this->client()->characters();
    }

    #[DataProvider('badResponses')]
    public function test_t7_server_errors_and_malformed_bodies_throw_api_exception(mixed $body, int $status): void
    {
        Http::fake([self::BASE.'/*' => Http::response($body, $status)]);

        $this->expectException(ApiException::class);

        $this->client()->characters();
    }

    public static function badResponses(): array
    {
        return [
            'HTTP 500' => ['', 500],
            'HTTP 503' => ['', 503],
            'HTTP 403' => ['', 403],
            'body is not JSON' => ['<html>oops</html>', 200],
            'JSON without results' => [['info' => ['count' => 1, 'pages' => 1]], 200],
            'JSON without info' => [['results' => []], 200],
            'results row is not an object' => [['info' => ['count' => 1, 'pages' => 1], 'results' => ['oops']], 200],
            'results row without id' => [['info' => ['count' => 1, 'pages' => 1], 'results' => [['name' => 'x']]], 200],
            'episode field is not a list' => [['info' => ['count' => 1, 'pages' => 1], 'results' => [['id' => 1, 'name' => 'x', 'episode' => 'nope']]], 200],
        ];
    }

    public function test_t8_connection_failure_becomes_api_exception_with_the_cause_attached(): void
    {
        Http::fake([self::BASE.'/*' => Http::failedConnection('cURL error 28: Operation timed out')]);

        try {
            $this->client()->characters();
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
            $this->assertInstanceOf(ConnectionException::class, $e->getPrevious());
        }
    }

    // ---------------------------------------------------------------- T9: episodes

    public function test_t9_episodes_are_fetched_in_one_batched_request(): void
    {
        Http::fake([self::BASE.'/episode/1,2,3' => Http::response([
            $this->episodeData(1, 'Pilot', 'S01E01'),
            $this->episodeData(2, 'Lawnmower Dog', 'S01E02'),
            $this->episodeData(3, 'Anatomy Park', 'S01E03'),
        ])]);

        $episodes = $this->client()->episodes([1, 2, 3]);

        Http::assertSentCount(1);
        $this->assertCount(3, $episodes);
        $this->assertInstanceOf(Episode::class, $episodes[0]);
        $this->assertSame(1, $episodes[0]->id);
        $this->assertSame('Pilot', $episodes[0]->name);
        $this->assertSame('S01E01', $episodes[0]->code);
        $this->assertSame('December 2, 2013', $episodes[0]->airDate);
    }

    public function test_t9b_a_single_episode_id_still_returns_a_list(): void
    {
        // The API returns one object, not a list, when asked for exactly one id.
        Http::fake([self::BASE.'/episode/7' => Http::response($this->episodeData(7, 'Raising Gazorpazorp', 'S01E07'))]);

        $episodes = $this->client()->episodes([7]);

        $this->assertCount(1, $episodes);
        $this->assertSame(7, $episodes[0]->id);
    }

    public function test_t9d_404_on_episodes_is_an_api_error_not_a_crash(): void
    {
        Http::fake([self::BASE.'/episode/*' => Http::response(['error' => 'Episode not found'], 404)]);

        $this->expectException(ApiException::class);

        $this->client()->episodes([1, 2]);
    }

    public function test_t9e_malformed_episode_rows_are_an_api_error(): void
    {
        Http::fake([self::BASE.'/episode/*' => Http::response(['oops', 42])]);

        $this->expectException(ApiException::class);

        $this->client()->episodes([1, 2]);
    }

    public function test_t9c_no_episode_ids_means_no_request(): void
    {
        Http::fake();

        $this->assertSame([], $this->client()->episodes([]));

        Http::assertNothingSent();
    }

    // ---------------------------------------------------------------- T10-T11: one character

    public function test_t10_single_character_is_fetched_by_id(): void
    {
        Http::fake([self::BASE.'/character/1' => Http::response($this->characterData(1, 'Rick Sanchez'))]);

        $character = $this->client()->character(1);

        Http::assertSent(fn (Request $r) => $r->url() === self::BASE.'/character/1');
        $this->assertSame('Rick Sanchez', $character->name);
        $this->assertSame('Earth (C-137)', $character->origin);
        $this->assertSame([1, 2], $character->episodeIds);
    }

    public function test_t11_404_on_a_single_character_throws_not_found(): void
    {
        Http::fake([self::BASE.'/character/999999' => Http::response(['error' => 'Character not found'], 404)]);

        $this->expectException(NotFoundException::class);

        $this->client()->character(999999);
    }

    // ---------------------------------------------------------------- helpers

    private function client(): ApiClient
    {
        return new ApiClient(self::BASE, timeout: 5);
    }
}
