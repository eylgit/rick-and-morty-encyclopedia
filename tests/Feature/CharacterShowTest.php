<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\RickAndMortyFixtures;
use Tests\TestCase;

/** Detail page. IDs D1..D5 match docs/tasks/.../test-cases.md. */
final class CharacterShowTest extends TestCase
{
    use RickAndMortyFixtures;

    public function test_d1_detail_page_shows_the_character_and_its_episodes(): void
    {
        Http::fake([
            self::API.'/character/1' => Http::response($this->characterData(1, 'Rick Sanchez')),
            self::API.'/episode/1,2' => Http::response([
                $this->episodeData(1, 'Pilot', 'S01E01'),
                $this->episodeData(2, 'Lawnmower Dog', 'S01E02'),
            ]),
        ]);

        $response = $this->get('/characters/1');

        $response->assertStatus(200);
        $response->assertSee('Rick Sanchez');
        $response->assertSee('/avatars/1', false);   // images are served through the app
        $response->assertSee('Human');          // species
        $response->assertSee('Alive');          // status
        $response->assertSee('Male');           // gender
        $response->assertSee('Earth (C-137)');  // origin
        $response->assertSee('Citadel of Ricks'); // last known location
        $response->assertSeeInOrder(['S01E01', 'Pilot', 'S01E02', 'Lawnmower Dog']);
        $response->assertSee('2 episodes');
        Http::assertSentCount(2);
    }

    public function test_d2_unknown_character_is_a_404_page(): void
    {
        Http::fake([
            self::API.'/character/999999' => Http::response(['error' => 'Character not found'], 404),
        ]);

        $this->get('/characters/999999')
            ->assertStatus(404)
            ->assertSee('Character 999999 does not exist');
    }

    public function test_d3_non_numeric_id_is_a_404_without_calling_the_api(): void
    {
        Http::fake();

        $this->get('/characters/abc')->assertStatus(404);
        $this->get('/characters/1.5')->assertStatus(404);
        $this->get('/characters/-1')->assertStatus(404);

        Http::assertNothingSent();
    }

    public function test_d4_all_episodes_are_fetched_in_one_request(): void
    {
        $ids = range(1, 51);
        $character = $this->characterData(1, 'Rick Sanchez');
        $character['episode'] = array_map(fn (int $id) => self::API."/episode/{$id}", $ids);
        $episodes = array_map(fn (int $id) => $this->episodeData($id, "Episode {$id}", sprintf('S%02dE%02d', intdiv($id - 1, 11) + 1, ($id - 1) % 11 + 1)), $ids);

        Http::fake([
            self::API.'/character/1' => Http::response($character),
            self::API.'/episode/*' => Http::response($episodes),
        ]);

        $response = $this->get('/characters/1');

        $response->assertStatus(200);
        $response->assertSee('51 episodes');
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $r) => $r->url() === self::API.'/episode/'.implode(',', $ids));
    }

    public function test_d4b_character_with_no_episodes_makes_no_episode_request(): void
    {
        $character = $this->characterData(7, 'Nobody');
        $character['episode'] = [];

        Http::fake([self::API.'/character/7' => Http::response($character)]);

        $response = $this->get('/characters/7');

        $response->assertStatus(200);
        $response->assertSee('No episodes');
        Http::assertSentCount(1);
    }

    public function test_d5_detail_page_links_back_to_the_list(): void
    {
        Http::fake([
            self::API.'/character/1' => Http::response($this->characterData(1, 'Rick Sanchez')),
            self::API.'/episode/*' => Http::response([$this->episodeData(1, 'Pilot', 'S01E01')]),
        ]);

        // route() builds the absolute URL from APP_URL, the same way the view does.
        $this->get('/characters/1')->assertSee('href="'.route('characters.index').'"', false);
    }
}
