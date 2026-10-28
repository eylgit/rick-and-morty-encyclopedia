<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\RickAndMortyFixtures;
use Tests\TestCase;

/** List page. IDs L1..L6 match docs/tasks/.../test-cases.md. The API is always faked. */
final class CharacterListTest extends TestCase
{
    use RickAndMortyFixtures;

    public function test_l1_list_page_shows_twenty_characters_and_the_page_position(): void
    {
        Http::fake([self::API.'/character*' => Http::response($this->pageBody())]);

        $response = $this->get('/characters');

        $response->assertStatus(200);
        for ($i = 1; $i <= 20; $i++) {
            $response->assertSee("Character {$i}");
        }
        $response->assertSee('Page 1 of 42');
        $response->assertSee('826 characters');
    }

    public function test_l2_page_parameter_is_forwarded_to_the_api(): void
    {
        Http::fake([self::API.'/character*' => Http::response($this->pageBody())]);

        $this->get('/characters?page=3')->assertStatus(200);

        Http::assertSent(fn (Request $r) => (int) $r->data()['page'] === 3);
    }

    #[DataProvider('invalidPages')]
    public function test_l3_invalid_page_redirects_to_the_first_page_without_calling_the_api(string $page): void
    {
        Http::fake();

        $this->get('/characters?page='.$page)->assertRedirect('/characters');

        Http::assertNothingSent();
    }

    public static function invalidPages(): array
    {
        return [
            'zero' => ['0'],
            'negative' => ['-1'],
            'text' => ['abc'],
            'decimal' => ['1.5'],
        ];
    }

    public function test_l4_no_results_shows_a_friendly_empty_state(): void
    {
        Http::fake([self::API.'/character*' => Http::response($this->nothingHere(), 404)]);

        $response = $this->get('/characters?page=999');

        $response->assertStatus(200);
        $response->assertSee('No characters found');
    }

    public function test_l5_root_redirects_to_the_list(): void
    {
        $this->get('/')->assertRedirect('/characters');
    }

    public function test_l6_page_links_point_to_the_neighbouring_pages(): void
    {
        Http::fake([self::API.'/character*' => Http::response($this->pageBody())]);

        $response = $this->get('/characters?page=2');

        $response->assertSee('/characters?page=1', false);
        $response->assertSee('/characters?page=3', false);
        $response->assertSee('Page 2 of 42');
    }

    public function test_l6b_each_character_links_to_its_detail_page(): void
    {
        Http::fake([self::API.'/character*' => Http::response($this->pageBody(results: 2))]);

        $response = $this->get('/characters');

        $response->assertSee('/characters/1', false);
        $response->assertSee('/characters/2', false);
        $response->assertSee('/avatars/1', false);   // images are served through the app
    }
}
