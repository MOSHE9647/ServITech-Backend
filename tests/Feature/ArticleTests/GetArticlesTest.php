<?php

namespace Tests\Feature\ArticleTests;

use App\Enums\UserRoles;
use App\Models\Article;
use App\Models\User;
use Database\Seeders\ArticleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetArticlesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Set up the test environment.
     * This method seeds the database before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            UserSeeder::class,
            ArticleSeeder::class,
        ]);
    }

    /**
     * Test that an authenticated admin user can retrieve all articles.
     * This ensures that only admin users can access the list of articles.
     */
    public function test_an_authenticated_admin_user_can_retrieve_all_articles(): void
    {
        // Given: An admin user and existing articles in the database
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $articles = Article::with(['images', 'category', 'subcategory'])->get();

        // When: The admin user attempts to retrieve all articles
        $response = $this->apiAs($admin, 'GET', route('articles.index'));
        // dd($response->json());

        // Then: The request should succeed, and the response should contain the articles
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message', 'data' => ['articles']]);

        $response->assertJsonFragment([
            'status' => 200,
            'message'=> __('messages.article.retrieved_all'),
            'data' => ['articles' => $articles->toArray()],
        ]);

    }

    /**
     * Test that an authenticated non-admin user cannot retrieve articles.
     * This ensures that only admin users can access the list of articles.
     */
    public function test_an_authenticated_non_admin_user_cannot_retrieve_articles(): void
    {
        // Given: A non-admin user
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        // When: The non-admin user attempts to retrieve all articles
        $response = $this->apiAs($user, 'GET', route('articles.index'));

        // Then: The request should fail with a 403 Forbidden status
        $response->assertStatus(403);
        $response->assertJsonStructure(['status', 'message', 'errors']);
    }

    /**
     * Test that a non-authenticated user cannot retrieve articles.
     * This ensures that only authenticated users can access the list of articles.
     */
    public function test_a_non_authenticated_user_cannot_retrieve_articles(): void
    {
        // When: A non-authenticated user attempts to retrieve all articles
        $response = $this->getJson(route('articles.index'));

        // Then: The request should fail with a 401 Unauthorized status
        $response->assertStatus(401);
        $response->assertJsonStructure(['status', 'message']);
    }

    /**
     * Test that articles with images, category, and subcategory are retrieved correctly.
     * This ensures that the response includes the related data.
     */
    public function test_articles_with_images_category_and_subcategory_are_retrieved_correctly(): void
    {
        // Given: An admin user and existing articles with related data
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $articles = Article::with(['images', 'category', 'subcategory'])->get();
        $this->assertNotNull($articles, 'No articles found with related data.');
        // dd($articles->toJson());

        // When: The admin user retrieves the articles
        $response = $this->apiAs($admin, 'GET', route('articles.index'));
        // dd($response->json());

        // Then: The response should include the related data
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'data' => ['articles' => $articles->toArray()],
        ]);
    }
}