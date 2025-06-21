<?php

namespace Tests\Feature\Article;

use App\Enums\UserRoles;
use App\Models\Article;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleShowByIdTest extends TestCase
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
     * Test that any user can view an article by ID without authentication.
     * This ensures that article viewing is publicly accessible.
     */
    public function test_can_view_article_by_id_without_authentication(): void
    {
        // Given: An existing article
        $article = Article::inRandomOrder()->first();
        $this->assertNotNull($article, 'Article not found');

        // When: Making a GET request to view the article by ID
        $response = $this->getJson(route('articles.showById', $article));

        // Then: The request should succeed and return the article details
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'article' => [
                    'id',
                    'category_id',
                    'subcategory_id',
                    'name',
                    'description',
                    'price',
                    'images'
                ]
            ]
        ]);

        $response->assertJsonFragment([
            'message' => __('messages.common.retrieved', ['item' => __('messages.entities.article.singular')])
        ]);

        // Verify the returned article data matches the expected article
        $responseData = $response->json();
        $articleData = $responseData['data']['article'];

        $this->assertEquals($article->id, $articleData['id']);
        $this->assertEquals($article->name, $articleData['name']);
        $this->assertEquals($article->description, $articleData['description']);
        $this->assertEquals($article->price, $articleData['price']);
        $this->assertEquals($article->category_id, $articleData['category_id']);
        $this->assertEquals($article->subcategory_id, $articleData['subcategory_id']);
        $this->assertIsArray($articleData['images']);
    }

    /**
     * Test that an authenticated regular user can view an article by ID.
     * This ensures that authenticated users can access article details.
     */
    public function test_authenticated_user_can_view_article_by_id(): void
    {
        // Given: An existing article and a regular user
        $article = Article::inRandomOrder()->first();
        $this->assertNotNull($article, 'Article not found');

        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        // When: An authenticated user attempts to view the article
        $response = $this->apiAs($user, 'GET', route('articles.showById', $article));

        // Then: The request should succeed and return the article details
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'article' => [
                    'id',
                    'category_id',
                    'subcategory_id',
                    'name',
                    'description',
                    'price',
                    'images'
                ]
            ]
        ]);

        $response->assertJsonFragment([
            'message' => __('messages.common.retrieved', ['item' => __('messages.entities.article.singular')])
        ]);
    }

    /**
     * Test that an authenticated admin user can view an article by ID.
     * This ensures that admin users can access article details.
     */
    public function test_authenticated_admin_can_view_article_by_id(): void
    {
        // Given: An existing article and an admin user
        $article = Article::inRandomOrder()->first();
        $this->assertNotNull($article, 'Article not found');

        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        // When: An admin user attempts to view the article
        $response = $this->apiAs($admin, 'GET', route('articles.showById', $article));

        // Then: The request should succeed and return the article details
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'article' => [
                    'id',
                    'category_id',
                    'subcategory_id',
                    'name',
                    'description',
                    'price',
                    'images'
                ]
            ]
        ]);

        $response->assertJsonFragment([
            'message' => __('messages.common.retrieved', ['item' => __('messages.entities.article.singular')])
        ]);
    }

    /**
     * Test that requesting a non-existent article returns a 404 error.
     * This ensures proper error handling for invalid article IDs.
     */
    public function test_viewing_non_existent_article_returns_404(): void
    {
        // Given: A non-existent article ID
        $nonExistentArticleId = 999999;

        // When: Making a GET request for the non-existent article
        $response = $this->getJson(route('articles.showById', $nonExistentArticleId));

        // Then: The request should return a 404 error
        $response->assertStatus(404);
        $response->assertJsonStructure(['status', 'message']);
    }

    /**
     * Test that soft-deleted articles return a 404 error.
     * This ensures that soft-deleted articles are not accessible.
     */
    public function test_viewing_soft_deleted_article_returns_404(): void
    {
        // Given: An article that we will soft delete
        $article = Article::inRandomOrder()->first();
        $this->assertNotNull($article, 'Article not found');

        $articleId = $article->id;

        // Soft delete the article
        $article->delete();
        $this->assertSoftDeleted('articles', ['id' => $articleId]);

        // When: Making a GET request for the soft-deleted article
        $response = $this->getJson(route('articles.showById', $articleId));

        // Then: The request should return a 404 error
        $response->assertStatus(404);
        $response->assertJsonStructure(['status', 'message']);
    }

    /**
     * Test that the article includes related images when present.
     * This ensures that images are properly loaded and returned.
     */
    public function test_article_includes_images_when_present(): void
    {
        // Given: An article with images
        $article = Article::with('images')->whereHas('images')->first();

        // If no article with images exists, create one with a mock image
        if (!$article) {
            $article = Article::inRandomOrder()->first();
            $article->images()->create([
                'path' => 'articles/test-image.jpg',
                'title' => 'Test Image',
                'alt' => 'Test image description',
                'imageable_type' => Article::class,
                'imageable_id' => $article->id
            ]);
            $article->refresh();
        }

        $this->assertNotNull($article, 'Article not found');
        $this->assertTrue($article->images->count() > 0, 'Article should have images');

        // When: Making a GET request to view the article
        $response = $this->getJson(route('articles.showById', $article));

        // Then: The response should include the images
        $response->assertStatus(200);

        $responseData = $response->json();
        $articleData = $responseData['data']['article'];

        $this->assertArrayHasKey('images', $articleData);
        $this->assertIsArray($articleData['images']);
        $this->assertGreaterThan(0, count($articleData['images']));

        // Verify image structure
        foreach ($articleData['images'] as $image) {
            $this->assertArrayHasKey('title', $image);
            $this->assertArrayHasKey('path', $image);
            $this->assertArrayHasKey('alt', $image);
        }
    }

    /**
     * Test that the article shows empty images array when no images are present.
     * This ensures proper handling of articles without images.
     */
    public function test_article_shows_empty_images_when_none_present(): void
    {
        // Given: An article without images
        $article = Article::doesntHave('images')->first();

        // If no such article exists, create one and ensure it has no images
        if (!$article) {
            $article = Article::inRandomOrder()->first();
            $article->images()->delete(); // Remove any existing images
        }

        $this->assertNotNull($article, 'Article not found');
        $this->assertEquals(0, $article->images()->count(), 'Article should have no images');

        // When: Making a GET request to view the article
        $response = $this->getJson(route('articles.showById', $article));

        // Then: The response should include an empty images array
        $response->assertStatus(200);

        $responseData = $response->json();
        $articleData = $responseData['data']['article'];

        $this->assertArrayHasKey('images', $articleData);
        $this->assertIsArray($articleData['images']);
        $this->assertCount(0, $articleData['images']);
    }

    /**
     * Test that the response contains the correct success message.
     * This ensures that the localized success message is returned.
     */
    public function test_response_contains_correct_success_message(): void
    {
        // Given: An existing article
        $article = Article::inRandomOrder()->first();
        $this->assertNotNull($article, 'Article not found');

        // When: Making a GET request to view the article
        $response = $this->getJson(route('articles.showById', $article));

        // Then: The response should contain the correct success message
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => 200,
            'message' => __('messages.common.retrieved', ['item' => __('messages.entities.article.singular')])
        ]);
    }

    /**
     * Test that the endpoint works with different types of article IDs.
     * This ensures robust handling of various ID formats.
     */
    public function test_endpoint_works_with_different_article_ids(): void
    {
        // Given: Multiple articles to test different ID patterns
        $articles = Article::take(3)->get();
        $this->assertGreaterThan(0, $articles->count(), 'Should have articles to test');

        foreach ($articles as $article) {
            // When: Making a GET request for each article
            $response = $this->getJson(route('articles.showById', $article->id));

            // Then: Each request should succeed
            $response->assertStatus(200);
            $response->assertJsonStructure([
                'status',
                'message',
                'data' => ['article']
            ]);

            $responseData = $response->json();
            $this->assertEquals($article->id, $responseData['data']['article']['id']);
        }
    }
}