<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\RickAndMorty\ApiClient;
use Tests\TestCase;

/**
 * V1: the client against the real API. Skipped unless RM_LIVE_TEST=1, so CI and the
 * normal suite never depend on the network or count against the API's rate limit.
 *
 *   RM_LIVE_TEST=1 php artisan test --filter LiveApiTest
 */
final class LiveApiTest extends TestCase
{
    public function test_v1_first_page_from_the_live_api(): void
    {
        if (getenv('RM_LIVE_TEST') !== '1') {
            $this->markTestSkipped('Set RM_LIVE_TEST=1 to run against the live API.');
        }

        $page = app(ApiClient::class)->characters(page: 1);

        $this->assertCount(20, $page->characters);
        $this->assertGreaterThan(1, $page->pages);
        $this->assertSame('Rick Sanchez', $page->characters[0]->name);
    }
}
