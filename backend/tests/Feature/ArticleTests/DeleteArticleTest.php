<?php

namespace Tests\Feature\ArticleTests;

use App\Enums\UserRoles;
use App\Models\Article;
use App\Models\User;
use Database\Seeders\ArticleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeleteArticleTest extends TestCase
{
    use RefreshDatabase; // Reset the database after each test

    /**
     * Set up the test environment.
     * This method seeds the database before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            // Seed the database with necessary data
            UserSeeder::class,
            ArticleSeeder::class,
        ]);
    }

    /**
     * Test that an authenticated admin user can delete an article.
     * This ensures that only admin users can delete articles successfully.
     */
    public function test_an_authenticated_admin_user_can_delete_an_article(): void
    {
        // Given: An existing article
        $article = Article::inRandomOrder()->first();
        $this->assertNotNull($article, __('messages.article.not_found')); // Ensure the article exists

        // When: An admin user attempts to delete the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'DELETE', route('articles.destroy', $article));

        // Then: The request should succeed, and the article should be deleted from the database
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message']);
        $this->assertDatabaseHas('articles', ['deleted_at' => now()->format('Y-m-d H:i:s')]);

        // Verify that associated images are deleted from the filesystem
        foreach ($article->images as $image) {
            Storage::assertMissing($image->path);
        }
    }

    /**
     * Test that an authenticated non-admin user cannot delete an article.
     * This ensures that only admin users can delete articles.
     */
    public function test_an_authenticated_non_admin_user_cannot_delete_an_article(): void
    {
        // Given: An existing article
        $article = Article::inRandomOrder()->first();
        $this->assertNotNull($article, __('messages.article.not_found')); // Ensure the article exists

        // When: A non-admin user attempts to delete the article
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'DELETE', route('articles.destroy', $article));

        // Then: The request should fail with a 403 Forbidden status
        $response->assertStatus(403);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $this->assertDatabaseHas('articles', ['deleted_at' => null]);
    }

    /**
     * Test that a non-authenticated user cannot delete an article.
     * This ensures that only authenticated users can delete articles.
     */
    public function test_a_non_authenticated_user_cannot_delete_an_article(): void
    {
        // Given: An existing article
        $article = Article::inRandomOrder()->first();
        $this->assertNotNull($article, __('messages.article.not_found')); // Ensure the article exists;

        // When: A non-authenticated user attempts to delete the article
        $response = $this->deleteJson(route('articles.destroy', $article));

        // Then: The request should fail with a 401 Unauthorized status
        $response->assertStatus(401);
        $response->assertJsonStructure(['status', 'message']);
        $this->assertDatabaseHas('articles', ['deleted_at' => null]);
    }

    /**
     * Test that deleting a non-existent article returns a 404 error.
     * This ensures that the API handles non-existent resources gracefully.
     */
    public function test_deleting_a_non_existent_article_returns_404(): void
    {
        // Given: A non-existent article ID
        $nonExistentArticleId = 999;

        // When: An admin user attempts to delete the non-existent article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'DELETE', route('articles.destroy', $nonExistentArticleId));
        // dd($response->json());

        // Then: The request should fail with a 404 Not Found status
        $response->assertStatus(404);
        $response->assertJsonStructure(['status', 'message']);

        $articleClass = Article::class;
        $response->assertJsonFragment([
            'status' => 404,
            'message'=> __('messages.not_found', ['attribute' => "[$articleClass]"]),
        ]);
    }
}