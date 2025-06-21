<?php

namespace Tests\Feature\Article;

use App\Enums\UserRoles;
use App\Models\Article;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleDeleteTest extends TestCase
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
     * Test that an authenticated admin user can delete an article.
     * This ensures that only admin users can delete articles successfully.
     */
    public function test_an_authenticated_admin_user_can_delete_an_article(): void
    {
        Storage::fake('public');

        // Given: An existing article
        $article = Article::inRandomOrder()->first();
        $this->assertNotNull($article, 'Article not found');

        // When: An admin user attempts to delete the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'DELETE', route('articles.destroy', $article));

        // Then: The request should succeed, and the article should be deleted from the database
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message']);

        $response->assertJsonFragment([
            'message' => __('messages.common.deleted', ['item' => __('messages.entities.article.singular')])
        ]);

        // Verify that the article is soft deleted
        $this->assertSoftDeleted('articles', ['id' => $article->id]);
    }

    /**
     * Test that an authenticated non-admin user cannot delete an article.
     * This ensures that only admin users can delete articles.
     */
    public function test_an_authenticated_non_admin_user_cannot_delete_an_article(): void
    {
        // Given: An existing article
        $article = Article::inRandomOrder()->first();
        $this->assertNotNull($article, 'Article not found');

        // When: A non-admin user attempts to delete the article
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $response = $this->apiAs($user, 'DELETE', route('articles.destroy', $article));

        // Then: The request should fail with a 403 Forbidden status
        $response->assertStatus(403);
        $response->assertJsonStructure(['status', 'message']);

        // Verify that the article was not deleted
        $this->assertDatabaseHas('articles', ['id' => $article->id, 'deleted_at' => null]);
    }

    /**
     * Test that a non-authenticated user cannot delete an article.
     * This ensures that only authenticated users can delete articles.
     */
    public function test_a_non_authenticated_user_cannot_delete_an_article(): void
    {
        // Given: An existing article
        $article = Article::inRandomOrder()->first();
        $this->assertNotNull($article, 'Article not found');

        // When: A non-authenticated user attempts to delete the article
        $response = $this->deleteJson(route('articles.destroy', $article));

        // Then: The request should fail with a 401 Unauthorized status
        $response->assertStatus(401);
        $response->assertJsonStructure(['status', 'message']);

        // Verify that the article was not deleted
        $this->assertDatabaseHas('articles', ['id' => $article->id, 'deleted_at' => null]);
    }

    /**
     * Test that deleting a non-existent article returns a 404 error.
     * This ensures that the API handles non-existent resources gracefully.
     */
    public function test_deleting_a_non_existent_article_returns_404(): void
    {
        // Given: A non-existent article ID
        $nonExistentArticleId = 999999;

        // When: An admin user attempts to delete the non-existent article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'DELETE', route('articles.destroy', $nonExistentArticleId));

        // Then: The request should fail with a 404 Not Found status
        $response->assertStatus(404);
        $response->assertJsonStructure(['status', 'message']);
    }

    /**
     * Test that an article with images is properly deleted including its images.
     * This ensures that when an article with associated images is deleted,
     * both the article and its images are properly removed.
     */
    public function test_deleting_article_with_images_removes_images(): void
    {
        Storage::fake('public');

        // Given: An article with images
        $article = Article::with('images')->whereHas('images')->first();

        // If no article with images exists, create one with fake images
        if (!$article) {
            $article = Article::inRandomOrder()->first();
            $article->images()->create([
                'path' => 'articles/test-image.jpg',
                'imageable_type' => Article::class,
                'imageable_id' => $article->id
            ]);
            $article->refresh();
        }

        $this->assertNotNull($article, 'Article not found');
        $this->assertTrue($article->images->count() > 0, 'Article should have images');

        $imageCount = $article->images->count();

        // When: An admin user deletes the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'DELETE', route('articles.destroy', $article));

        // Then: The article and its images should be deleted
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message']);

        // Verify the article is soft deleted
        $this->assertSoftDeleted('articles', ['id' => $article->id]);

        // Verify that the images records are also deleted from the database
        $this->assertEquals(0, $article->fresh()->images()->count());
    }

    /**
     * Test that an already deleted article cannot be deleted again.
     * This ensures proper handling of soft-deleted articles.
     */
    public function test_deleting_already_deleted_article_returns_404(): void
    {
        // Given: An article that is already soft deleted
        $article = Article::inRandomOrder()->first();
        $this->assertNotNull($article, 'Article not found');

        // Soft delete the article first
        $article->delete();
        $this->assertSoftDeleted('articles', ['id' => $article->id]);

        // When: An admin user attempts to delete the already deleted article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'DELETE', route('articles.destroy', $article->id));

        // Then: The request should fail with a 404 Not Found status
        $response->assertStatus(404);
        $response->assertJsonStructure(['status', 'message']);
    }

    /**
     * Test that the correct success message is returned when deleting an article.
     * This ensures that the API returns the expected localized message.
     */
    public function test_successful_deletion_returns_correct_message(): void
    {
        // Given: An existing article
        $article = Article::inRandomOrder()->first();
        $this->assertNotNull($article, 'Article not found');

        // When: An admin user deletes the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'DELETE', route('articles.destroy', $article));

        // Then: The response should contain the correct success message
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'message' => __('messages.common.deleted', ['item' => __('messages.entities.article.singular')])
        ]);
        $response->assertJsonFragment(['status' => 200]);
    }
}