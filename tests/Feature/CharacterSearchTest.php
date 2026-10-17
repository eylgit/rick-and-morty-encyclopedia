<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\RickAndMortyFixtures;
use Tests\TestCase;

/** Search and filters on the list page. IDs S1..S5 match docs/tasks/.../test-cases.md. */
final class CharacterSearchTest extends TestCase
{
    use RickAndMortyFixtures;

    public function test_s1_name_search_is_forwarded_and_the_box_keeps_its_value(): void
    {
        Http::fake([self::API.'/character*' => Http::response($this->pageBody(results: 3, pages: 1, count: 3))]);

        $response = $this->get('/characters?name=rick');

        $response->assertStatus(200);
        Http::assertSent(fn (Request $r) => ($r->data()['name'] ?? null) === 'rick');
        $response->assertSee('value="rick"', false);
    }

    public function test_s2_filters_are_forwarded_together(): void
    {
        Http::fake([self::API.'/character*' => Http::response($this->pageBody(results: 3, pages: 1, count: 3))]);

        $response = $this->get('/characters?status=alive&gender=female&species=human');

        $response->assertStatus(200);
        // The client forwards the documented keys in its own fixed order and drops the empty ones.
        Http::assertSent(fn (Request $r) => $r->data() === [
            'page' => 1, 'status' => 'alive', 'species' => 'human', 'gender' => 'female',
        ]);
    }

    #[DataProvider('invalidFilters')]
    public function test_s3_invalid_filter_values_redirect_to_the_list_without_calling_the_api(string $query): void
    {
        Http::fake();

        $this->get('/characters?'.$query)->assertRedirect('/characters');

        Http::assertNothingSent();
    }

    public static function invalidFilters(): array
    {
        return [
            'unknown status' => ['status=zombie'],
            'unknown gender' => ['gender=robot'],
            'name too long' => ['name='.str_repeat('a', 101)],
            'name is an array' => ['name[]=rick'],
        ];
    }

    public function test_s4_search_text_is_escaped_in_the_page(): void
    {
        Http::fake([self::API.'/character*' => Http::response($this->nothingHere(), 404)]);

        $response = $this->get('/characters?name='.urlencode('<script>alert(1)</script>'));

        $response->assertStatus(200);
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_s5_page_links_keep_the_search_and_filters(): void
    {
        Http::fake([self::API.'/character*' => Http::response($this->pageBody(results: 20, pages: 4, count: 70))]);

        $response = $this->get('/characters?name=rick&status=alive&page=2');

        $response->assertStatus(200);
        $response->assertSee('name=rick&amp;status=alive&amp;page=3', false);
        $response->assertSee('name=rick&amp;status=alive&amp;page=1', false);
    }

    public function test_s5b_page_links_do_not_carry_unknown_parameters(): void
    {
        Http::fake([self::API.'/character*' => Http::response($this->pageBody(results: 20, pages: 4, count: 70))]);

        $response = $this->get('/characters?name=rick&evil=yes&page=2');

        $response->assertStatus(200);
        $response->assertDontSee('evil=yes', false);
    }

    public function test_s5c_dropdowns_keep_their_selection(): void
    {
        Http::fake([self::API.'/character*' => Http::response($this->pageBody(results: 3, pages: 1, count: 3))]);

        $response = $this->get('/characters?status=dead&gender=genderless');

        $response->assertSee('value="dead" selected', false);
        $response->assertSee('value="genderless" selected', false);
    }

    public function test_s6_no_match_offers_to_clear_the_search(): void
    {
        Http::fake([self::API.'/character*' => Http::response($this->nothingHere(), 404)]);

        $response = $this->get('/characters?name=nobody');

        $response->assertStatus(200);
        $response->assertSee('No characters found');
        $response->assertSee('Clear the search');
    }
}
