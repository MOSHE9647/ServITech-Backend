<?php

namespace Tests\Feature\Article;

use App\Enums\UserRoles;
use App\Models\Article;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleIndexTest extends TestCase
{
    use RefreshDatabase; // Reset the database after each test

    /**
     * Set up the test environment.
     * This method seeds the database before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([DatabaseSeeder::class]); // Seed the database with initial data
        Storage::fake('public'); // Use a fake storage disk for testing
    }

    /**
     * Test that any user can retrieve the list of articles.
     * This ensures that the articles endpoint is publicly accessible.
     */
    public function test_any_user_can_retrieve_articles_list(): void
    {
        // When: A request is made to get all articles
        $response = $this->getJson(route('articles.index'));

        // Then: The request should succeed
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'articles' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'price',
                        'category_id',
                        'subcategory_id',
                        'created_at',
                        'updated_at',
                        'category',
                        'subcategory',
                        'images'
                    ]
                ]
            ]
        ]);

        $response->assertJsonFragment([
            'message' => __('messages.common.retrieved_all', ['items' => __('messages.entities.article.plural')])
        ]);
    }

    /**
     * Test that authenticated user can retrieve the list of articles.
     * This ensures that authenticated users can also access the articles endpoint.
     */
    public function test_authenticated_user_can_retrieve_articles_list(): void
    {
        // Given: An authenticated user
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        // When: The user requests the articles list
        $response = $this->apiAs($user, 'GET', route('articles.index'));

        // Then: The request should succeed
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'articles'
            ]
        ]);
    }

    /**
     * Test that admin user can retrieve the list of articles.
     * This ensures that admin users can also access the articles endpoint.
     */
    public function test_admin_user_can_retrieve_articles_list(): void
    {
        // Given: An authenticated admin user
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        // When: The admin requests the articles list
        $response = $this->apiAs($admin, 'GET', route('articles.index'));

        // Then: The request should succeed
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'articles'
            ]
        ]);
    }

    /**
     * Test that articles are returned with their relationships.
     * This ensures that articles include category, subcategory, and images data.
     */
    public function test_articles_are_returned_with_relationships(): void
    {
        // Given: Articles exist in the database
        $articleCount = Article::count();
        $this->assertGreaterThan(0, $articleCount, 'No articles found in database');

        // When: A request is made to get all articles
        $response = $this->getJson(route('articles.index'));

        // Then: The articles should include their relationships
        $response->assertStatus(200);

        $articles = $response->json('data.articles');
        $this->assertNotEmpty($articles, 'Articles array should not be empty');

        // Check that the first article has the expected relationships
        $firstArticle = $articles[0];
        $this->assertArrayHasKey('category', $firstArticle, 'Article should have category relationship');
        $this->assertArrayHasKey('subcategory', $firstArticle, 'Article should have subcategory relationship');
        $this->assertArrayHasKey('images', $firstArticle, 'Article should have images relationship');
    }    /**
         * Test that articles are ordered by ID in descending order.
         * This ensures that newer articles appear first in the list.
         */
    public function test_articles_are_ordered_by_id_descending(): void
    {
        // Given: Multiple articles exist (create additional ones if needed)
        $initialCount = Article::count();

        // If we don't have enough articles, create more
        if ($initialCount < 2) {
            $category = \App\Models\Category::first();
            $subcategory = $category->subcategories()->first();

            // Create additional articles to have at least 3 total
            Article::factory(3 - $initialCount)->create([
                'category_id' => $category->id,
                'subcategory_id' => $subcategory->id,
            ]);
        }

        $articleCount = Article::count();
        $this->assertGreaterThan(1, $articleCount, 'Need at least 2 articles for ordering test');

        // When: A request is made to get all articles
        $response = $this->getJson(route('articles.index'));

        // Then: Articles should be ordered by ID in descending order
        $response->assertStatus(200);

        $articles = $response->json('data.articles');
        $this->assertNotEmpty($articles, 'Articles array should not be empty');

        // Check that articles are ordered by ID descending
        $ids = array_column($articles, 'id');
        $sortedIds = $ids;
        rsort($sortedIds); // Sort in descending order

        $this->assertEquals($sortedIds, $ids, 'Articles should be ordered by ID in descending order');
    }

    /**
     * Test the response structure and data types.
     * This ensures that the API response has the expected structure and data types.
     */
    public function test_response_structure_and_data_types(): void
    {
        // When: A request is made to get all articles
        $response = $this->getJson(route('articles.index'));

        // Then: The response should have the correct structure and data types
        $response->assertStatus(200);

        $responseData = $response->json();

        // Check main response structure
        $this->assertArrayHasKey('status', $responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('data', $responseData);

        // Check data structure
        $this->assertArrayHasKey('articles', $responseData['data']);
        $this->assertIsArray($responseData['data']['articles']);

        // Check status is an integer
        $this->assertIsInt($responseData['status']);
        $this->assertEquals(200, $responseData['status']);

        // Check message is a string
        $this->assertIsString($responseData['message']);
    }

    /**
     * Test that the endpoint returns correct content type.
     * This ensures that the API returns JSON content type.
     */
    public function test_response_content_type_is_json(): void
    {
        // When: A request is made to get all articles
        $response = $this->getJson(route('articles.index'));

        // Then: The response should have JSON content type
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Test articles list when no articles exist.
     * This ensures that the API handles empty results gracefully.
     */
    public function test_articles_list_when_no_articles_exist(): void
    {
        // Given: No articles in the database
        Article::query()->delete(); // Delete all articles
        $this->assertEquals(0, Article::count(), 'Articles should be deleted');

        // When: A request is made to get all articles
        $response = $this->getJson(route('articles.index'));

        // Then: The response should still be successful with empty array
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'articles'
            ]
        ]);

        $articles = $response->json('data.articles');
        $this->assertIsArray($articles, 'Articles should be an array');
        $this->assertEmpty($articles, 'Articles array should be empty');
    }

    /**
     * Test that soft deleted articles are not included in the list.
     * This ensures that only active articles are returned.
     */
    public function test_soft_deleted_articles_are_not_included(): void
    {
        // Given: An article that we will soft delete
        $article = Article::first();
        $this->assertNotNull($article, 'Article not found');

        $initialCount = Article::count();

        // Soft delete the article
        $article->delete();
        $this->assertSoftDeleted('articles', ['id' => $article->id]);

        // When: A request is made to get all articles
        $response = $this->getJson(route('articles.index'));

        // Then: The soft deleted article should not be included
        $response->assertStatus(200);

        $articles = $response->json('data.articles');
        $articleIds = array_column($articles, 'id');

        $this->assertNotContains($article->id, $articleIds, 'Soft deleted article should not be in the list');
        $this->assertCount($initialCount - 1, $articles, 'Article count should be reduced by 1');
    }
}