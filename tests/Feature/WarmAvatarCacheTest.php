<?php

declare(strict_types=1);

namespace Tests\Feature;

use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\Support\RickAndMortyFixtures;
use Tests\TestCase;

/** The cache-warming command. IDs W1..W6 match docs/tasks/2026-10-28_warm-avatar-cache/test-cases.md. */
final class WarmAvatarCacheTest extends TestCase
{
    use RickAndMortyFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Sleep::fake();
    }

    public function test_w1_an_empty_cache_is_filled_one_image_per_id(): void
    {
        $this->fakeApi(count: 3);

        $this->artisan('avatars:warm')
            ->expectsOutputToContain('Fetched 3, already cached 0, failed 0')
            ->assertExitCode(0);

        Http::assertSentCount(4);   // the list page for the count, then three images
        foreach ([1, 2, 3] as $id) {
            $this->assertSame(self::JPEG, Cache::get("rickandmorty:avatar:{$id}"));
        }
    }

    public function test_w2_images_already_in_the_cache_are_not_requested(): void
    {
        $this->fakeApi(count: 3);
        Cache::put('rickandmorty:avatar:1', self::JPEG, 60);
        Cache::put('rickandmorty:avatar:3', self::JPEG, 60);

        $this->artisan('avatars:warm')
            ->expectsOutputToContain('Fetched 1, already cached 2, failed 0')
            ->assertExitCode(0);

        Http::assertSentCount(2);   // the list page, then id 2 only
        Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/avatar/1.jpeg'));
    }

    public function test_w3_a_404_is_counted_as_failed_and_the_run_continues(): void
    {
        $this->fakeApi(count: 3, answers: [2 => Http::response('', 404)]);

        $this->artisan('avatars:warm')
            ->expectsOutputToContain('Fetched 2, already cached 0, failed 1')
            ->assertExitCode(1);

        $this->assertSame(self::JPEG, Cache::get('rickandmorty:avatar:1'));
        $this->assertNull(Cache::get('rickandmorty:avatar:2'));
        $this->assertSame(self::JPEG, Cache::get('rickandmorty:avatar:3'));
    }

    public function test_w4_a_429_stops_the_run_and_names_the_id(): void
    {
        $this->fakeApi(count: 3, answers: [2 => Http::response('', 429)]);

        $this->artisan('avatars:warm')
            ->expectsOutputToContain('id 2')
            ->assertExitCode(1);

        $this->assertSame(self::JPEG, Cache::get('rickandmorty:avatar:1'));
        $this->assertNull(Cache::get('rickandmorty:avatar:3'));
        Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/avatar/3.jpeg'));
    }

    public function test_w5_a_500_is_counted_as_failed_and_the_run_continues(): void
    {
        $this->fakeApi(count: 3, answers: [2 => Http::response('', 500)]);

        $this->artisan('avatars:warm')
            ->expectsOutputToContain('Fetched 2, already cached 0, failed 1')
            ->assertExitCode(1);

        $this->assertSame(self::JPEG, Cache::get('rickandmorty:avatar:3'));
    }

    public function test_w6_the_pause_follows_each_real_fetch_and_no_cache_hit(): void
    {
        $this->fakeApi(count: 3);
        Cache::put('rickandmorty:avatar:1', self::JPEG, 60);

        $this->artisan('avatars:warm')->assertExitCode(0);

        Sleep::assertSleptTimes(2);
        Sleep::assertSlept(fn (CarbonInterval $pause) => $pause->totalSeconds === 1.0, times: 2);
    }

    // ---------------------------------------------------------------- helpers

    /**
     * The list page answers with the given count; every avatar answers with an image unless
     * $answers names a different response for that id.
     *
     * @param  array<int, mixed>  $answers
     */
    private function fakeApi(int $count, array $answers = []): void
    {
        $fakes = [];
        foreach ($answers as $id => $answer) {
            $fakes[self::API."/character/avatar/{$id}.jpeg"] = $answer;
        }
        $fakes[self::API.'/character/avatar/*'] = $this->imageResponse();
        $fakes[self::API.'/character*'] = Http::response($this->pageBody(results: $count, pages: 1, count: $count));

        Http::fake($fakes);
    }
}
