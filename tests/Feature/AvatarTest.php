<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\Support\RickAndMortyFixtures;
use Tests\TestCase;

/** Images served through the app. IDs P1..P7 match docs/tasks/2026-10-28_proxy-avatars/test-cases.md. */
final class AvatarTest extends TestCase
{
    use RickAndMortyFixtures;

    public function test_p1_an_avatar_is_served_as_a_jpeg_with_long_cache_headers(): void
    {
        Http::fake([self::API.'/character/avatar/1.jpeg' => $this->imageResponse()]);

        $response = $this->get('/avatars/1');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/jpeg');
        $this->assertSame(self::JPEG, $response->getContent());
        $this->assertTrue($response->headers->hasCacheControlDirective('public'));
        $this->assertSame('7776000', $response->headers->getCacheControlDirective('max-age'));
        $this->assertTrue($response->headers->hasCacheControlDirective('immutable'));
        $this->assertNotEmpty($response->headers->get('ETag'));
    }

    public function test_p2_the_same_avatar_twice_calls_the_api_once(): void
    {
        Http::fake([self::API.'/character/avatar/*' => $this->imageResponse()]);

        $first = $this->get('/avatars/1')->assertStatus(200);
        $second = $this->get('/avatars/1')->assertStatus(200);

        Http::assertSentCount(1);
        $this->assertSame($first->getContent(), $second->getContent());
    }

    public function test_p3_a_matching_etag_gets_304_without_asking_the_api(): void
    {
        Http::fake([self::API.'/character/avatar/*' => $this->imageResponse()]);

        $etag = $this->get('/avatars/1')->headers->get('ETag');
        $response = $this->get('/avatars/1', ['If-None-Match' => $etag]);

        $response->assertStatus(304);
        $this->assertSame('', $response->getContent());
        Http::assertSentCount(1);
    }

    public function test_p4_a_rate_limited_avatar_shows_the_503_page_and_is_not_cached(): void
    {
        Http::fake([self::API.'/character/avatar/*' => Http::response('', 429)]);

        $this->get('/avatars/1')->assertStatus(503);
        $this->get('/avatars/1')->assertStatus(503);

        Http::assertSentCount(2);   // nothing was remembered after the failure
    }

    public function test_p5_an_unknown_avatar_is_a_404(): void
    {
        Http::fake([self::API.'/character/avatar/*' => Http::response('', 404)]);

        $this->get('/avatars/999999')->assertStatus(404);
    }

    public function test_p6_a_non_numeric_id_is_a_404_before_any_request(): void
    {
        Http::fake();

        $this->get('/avatars/abc')->assertStatus(404);

        Http::assertNothingSent();
    }

    public function test_p7_both_pages_point_every_image_at_the_app_not_the_api(): void
    {
        Http::fake([
            self::API.'/character/1' => Http::response($this->characterData(1, 'Rick Sanchez')),
            self::API.'/episode/*' => Http::response([$this->episodeData(1, 'Pilot', 'S01E01')]),
            self::API.'/character*' => Http::response($this->pageBody(results: 2)),
        ]);

        foreach (['/characters', '/characters/1'] as $url) {
            $html = $this->get($url)->assertStatus(200)->getContent();

            preg_match_all('/<img[^>]+src="([^"]*)"/', $html, $matches);
            $this->assertNotEmpty($matches[1], "no <img> on {$url}");
            foreach ($matches[1] as $src) {
                $this->assertStringStartsWith('/avatars/', $src, "image on {$url} points elsewhere: {$src}");
            }
        }
    }
}
