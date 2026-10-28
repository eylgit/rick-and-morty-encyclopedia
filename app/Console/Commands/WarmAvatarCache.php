<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\RickAndMorty\ApiClient;
use App\RickAndMorty\ApiException;
use App\RickAndMorty\NotFoundException;
use App\RickAndMorty\RateLimitedException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Sleep;

/**
 * Fetches every character image into the cache, one a second, so no visitor ever pays for a
 * cold image and the API never sees a burst. Safe to run again: cached ids are skipped.
 */
final class WarmAvatarCache extends Command
{
    protected $signature = 'avatars:warm';

    protected $description = 'Fetch every character image into the cache, one a second';

    /** Seconds between requests to the API. The limiter trips on bursts, not on a trickle. */
    private const PAUSE_SECONDS = 1;

    public function handle(ApiClient $api): int
    {
        // The same entry CharacterController reads for the first page, so this costs nothing
        // when a visitor has already opened the list.
        $count = Cache::remember(
            'rickandmorty:list:page=1',
            config('rickandmorty.cache_seconds'),
            fn () => $api->characters(page: 1),
        )->count;

        $this->line("Warming {$count} images, one a second. Already cached ids are skipped.");

        $fetched = 0;
        $cached = 0;
        $failed = 0;

        for ($id = 1; $id <= $count; $id++) {
            $key = "rickandmorty:avatar:{$id}";

            if (Cache::has($key)) {
                $cached++;

                continue;
            }

            try {
                Cache::put($key, $api->avatar($id), config('rickandmorty.avatar_cache_seconds'));
                $fetched++;
            } catch (RateLimitedException) {
                $this->error("Stopped at id {$id}: the API is rate-limiting this application. Run again in a minute; it continues from here.");
                $this->summary($fetched, $cached, $failed);

                return self::FAILURE;
            } catch (NotFoundException|ApiException $e) {
                $failed++;
                $this->warn("id {$id}: {$e->getMessage()}");
            }

            // After every request that reached the API, whether it succeeded or not.
            Sleep::for(self::PAUSE_SECONDS)->seconds();
        }

        $this->summary($fetched, $cached, $failed);

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function summary(int $fetched, int $cached, int $failed): void
    {
        $this->info("Fetched {$fetched}, already cached {$cached}, failed {$failed}.");
    }
}
