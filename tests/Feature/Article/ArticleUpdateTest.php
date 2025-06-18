<?php

namespace Tests\Feature\Article;

use App\Enums\UserRoles;
use App\Models\Article;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\User;
use Database\Seeders\ArticleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleUpdateTest extends TestCase
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
     * Test that an authenticated admin user can update an article.
     * This ensures that only admin users can update articles successfully.
     */
    public function test_an_authenticated_admin_user_can_update_an_article(): void
    {
        Storage::fake('public');
        
        // Given: An existing article
        $article = Article::inRandomOrder()->first();
        $this->assertNotNull($article, 'Article not found');

        // When: An admin user attempts to update the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $updateData = [
            'name' => 'Updated Article Name',
            'description' => 'Updated description for the article with more details.',
            'price' => 99.99,
            'category_id' => $article->category_id,
            'subcategory_id' => $article->subcategory_id,
        ];

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should succeed, and the article should be updated in the database
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'article' => [
                    'id',
                    'name',
                    'description',
                    'price',
                    'category_id',
                    'subcategory_id',
                    'images'
                ]
            ]
        ]);

        $response->assertJsonFragment([
            'message' => __('messages.common.updated', ['item' => __('messages.entities.article.singular')])
        ]);

        // Verify that the article is updated in the database
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'name' => $updateData['name'],
            'description' => $updateData['description'],
            'price' => $updateData['price'],
        ]);

        // Verify the response contains the updated data
        $responseData = $response->json();
        $articleData = $responseData['data']['article'];
        $this->assertEquals($updateData['name'], $articleData['name']);
        $this->assertEquals($updateData['description'], $articleData['description']);
        $this->assertEquals($updateData['price'], $articleData['price']);
    }

    /**
     * Test that an authenticated non-admin user cannot update an article.
     * This ensures that only admin users can update articles.
     */
    public function test_an_authenticated_non_admin_user_cannot_update_an_article(): void
    {
        // Given: An existing article
        $article = Article::inRandomOrder()->first();
        $this->assertNotNull($article, 'Article not found');

        // When: A non-admin user attempts to update the article
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $updateData = [
            'name' => 'Updated Article Name',
            'description' => 'Updated description for the article.',
            'price' => 99.99,
            'category_id' => $article->category_id,
            'subcategory_id' => $article->subcategory_id,
        ];

        $response = $this->apiAs($user, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should fail with a 403 Forbidden status
        $response->assertStatus(403);
        $response->assertJsonStructure(['status', 'message']);

        // Verify that the article was not updated in the database
        $this->assertDatabaseMissing('articles', [
            'id' => $article->id,
            'name' => $updateData['name'],
        ]);
    }

    /**
     * Test that a non-authenticated user cannot update an article.
     * This ensures that only authenticated users can update articles.
     */
    public function test_a_non_authenticated_user_cannot_update_an_article(): void
    {
        // Given: An existing article
        $article = Article::inRandomOrder()->first();
        $this->assertNotNull($article, 'Article not found');

        $updateData = [
            'name' => 'Updated Article Name',
            'description' => 'Updated description for the article.',
            'price' => 99.99,
            'category_id' => $article->category_id,
            'subcategory_id' => $article->subcategory_id,
        ];

        // When: A non-authenticated user attempts to update the article
        $response = $this->putJson(route('articles.update', $article), $updateData);

        // Then: The request should fail with a 401 Unauthorized status
        $response->assertStatus(401);
        $response->assertJsonStructure(['status', 'message']);

        // Verify that the article was not updated in the database
        $this->assertDatabaseMissing('articles', [
            'id' => $article->id,
            'name' => $updateData['name'],
        ]);
    }

    /**
     * Test that updating a non-existent article returns a 404 error.
     * This ensures that the API handles non-existent resources gracefully.
     */
    public function test_updating_a_non_existent_article_returns_404(): void
    {
        // Given: A non-existent article ID
        $nonExistentArticleId = 999999;

        // When: An admin user attempts to update the non-existent article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $updateData = [
            'name' => 'Updated Article Name',
            'description' => 'Updated description for the article.',
            'price' => 99.99,
            'category_id' => 1,
            'subcategory_id' => 1,
        ];

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $nonExistentArticleId), $updateData);

        // Then: The request should fail with a 404 Not Found status
        $response->assertStatus(404);
        $response->assertJsonStructure(['status', 'message']);
    }

    /**
     * Test that the name field is required for update.
     * This ensures that missing the name field returns a 422 status with validation errors.
     */
    public function test_name_must_be_required_for_update(): void
    {
        // Given: An existing article and update data without name
        $article = Article::inRandomOrder()->first();
        $this->assertNotNull($article, 'Article not found');

        $updateData = [
            'description' => 'Updated description for the article.',
            'price' => 99.99,
            'category_id' => $article->category_id,
            'subcategory_id' => $article->subcategory_id,
        ];

        // When: An admin user attempts to update the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['name']]);
    }

    /**
     * Test that the name must be a string for update.
     * This ensures that non-string values for the name field return a 422 status with validation errors.
     */
    public function test_name_must_be_a_string_for_update(): void
    {
        // Given: An existing article and update data with non-string name
        $article = Article::inRandomOrder()->first();
        $this->assertNotNull($article, 'Article not found');

        $updateData = [
            'name' => 12345, // Non-string value
            'description' => 'Updated description for the article.',
            'price' => 99.99,
            'category_id' => $article->category_id,
            'subcategory_id' => $article->subcategory_id,
        ];

        // When: An admin user attempts to update the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['name']]);
    }

    /**
     * Test that the name must have at least 3 characters for update.
     * This ensures that short names return a 422 status with validation errors.
     */
    public function test_name_must_have_at_least_3_characters_for_update(): void
    {
        // Given: An existing article and update data with short name
        $article = Article::inRandomOrder()->first();
        $this->assertNotNull($article, 'Article not found');

        $updateData = [
            'name' => 'AB', // Less than 3 characters
            'description' => 'Updated description for the article.',
            'price' => 99.99,
            'category_id' => $article->category_id,
            'subcategory_id' => $article->subcategory_id,
        ];

        // When: An admin user attempts to update the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['name']]);
    }

    /**
     * Test that the description field is required for update.
     * This ensures that missing the description field returns a 422 status with validation errors.
     */
    public function test_description_must_be_required_for_update(): void
    {
        // Given: An existing article and update data without description
        $article = Article::inRandomOrder()->first();
        $this->assertNotNull($article, 'Article not found');

        $updateData = [
            'name' => 'Updated Article Name',
            'price' => 99.99,
            'category_id' => $article->category_id,
            'subcategory_id' => $article->subcategory_id,
        ];

        // When: An admin user attempts to update the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['description']]);
    }

    /**
     * Test that the price field is required for update.
     * This ensures that missing the price field returns a 422 status with validation errors.
     */
    public function test_price_must_be_required_for_update(): void
    {
        // Given: An existing article and update data without price
        $article = Article::inRandomOrder()->first();
        $this->assertNotNull($article, 'Article not found');

        $updateData = [
            'name' => 'Updated Article Name',
            'description' => 'Updated description for the article.',
            'category_id' => $article->category_id,
            'subcategory_id' => $article->subcategory_id,
        ];

        // When: An admin user attempts to update the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['price']]);
    }

    /**
     * Test that the price must be numeric for update.
     * This ensures that non-numeric values for the price field return a 422 status with validation errors.
     */
    public function test_price_must_be_numeric_for_update(): void
    {
        // Given: An existing article and update data with non-numeric price
        $article = Article::inRandomOrder()->first();
        $this->assertNotNull($article, 'Article not found');

        $updateData = [
            'name' => 'Updated Article Name',
            'description' => 'Updated description for the article.',
            'price' => 'not-a-number',
            'category_id' => $article->category_id,
            'subcategory_id' => $article->subcategory_id,
        ];

        // When: An admin user attempts to update the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['price']]);
    }

    /**
     * Test that category_id must exist for update.
     * This ensures that invalid category IDs return a 422 status with validation errors.
     */
    public function test_category_id_must_exist_for_update(): void
    {
        // Given: An existing article and update data with non-existent category_id
        $article = Article::inRandomOrder()->first();
        $this->assertNotNull($article, 'Article not found');

        $updateData = [
            'name' => 'Updated Article Name',
            'description' => 'Updated description for the article.',
            'price' => 99.99,
            'category_id' => 999999, // Non-existent category
            'subcategory_id' => $article->subcategory_id,
        ];

        // When: An admin user attempts to update the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['category_id']]);
    }

    /**
     * Test that subcategory_id must exist for update.
     * This ensures that invalid subcategory IDs return a 422 status with validation errors.
     */
    public function test_subcategory_id_must_exist_for_update(): void
    {
        // Given: An existing article and update data with non-existent subcategory_id
        $article = Article::inRandomOrder()->first();
        $this->assertNotNull($article, 'Article not found');

        $updateData = [
            'name' => 'Updated Article Name',
            'description' => 'Updated description for the article.',
            'price' => 99.99,
            'category_id' => $article->category_id,
            'subcategory_id' => 999999, // Non-existent subcategory
        ];

        // When: An admin user attempts to update the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['subcategory_id']]);
    }

    /**
     * Test that an admin can update an article with new images.
     * This ensures that image updates work correctly during article update.
     */
    public function test_admin_can_update_article_with_new_images(): void
    {
        Storage::fake('public');
        
        // Given: An existing article
        $article = Article::inRandomOrder()->first();
        $this->assertNotNull($article, 'Article not found');

        // Create fake image files
        $image1 = UploadedFile::fake()->image('test1.jpg', 640, 480)->size(100);
        $image2 = UploadedFile::fake()->image('test2.png', 800, 600)->size(150);

        $updateData = [
            'name' => 'Updated Article with Images',
            'description' => 'Updated description with new images.',
            'price' => 149.99,
            'category_id' => $article->category_id,
            'subcategory_id' => $article->subcategory_id,
            'images' => [$image1, $image2],
        ];

        // When: An admin user attempts to update the article with images
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should succeed
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'article' => [
                    'id',
                    'name',
                    'description',
                    'price',
                    'category_id',
                    'subcategory_id',
                    'images'
                ]
            ]
        ]);

        // Verify that the article is updated in the database
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'name' => $updateData['name'],
            'description' => $updateData['description'],
            'price' => $updateData['price'],
        ]);

        // Verify that new images are created for the article
        $responseData = $response->json();
        $articleData = $responseData['data']['article'];
        $this->assertNotEmpty($articleData['images'], 'Article should have images');
    }

    /**
     * Test that validation works for invalid image files during update.
     * This ensures that only valid image files are accepted.
     */
    public function test_validation_fails_for_invalid_image_files_during_update(): void
    {
        Storage::fake('public');
        
        // Given: An existing article
        $article = Article::inRandomOrder()->first();
        $this->assertNotNull($article, 'Article not found');

        // Create an invalid file (not an image)
        $invalidFile = UploadedFile::fake()->create('document.pdf', 100);

        $updateData = [
            'name' => 'Updated Article Name',
            'description' => 'Updated description for the article.',
            'price' => 99.99,
            'category_id' => $article->category_id,
            'subcategory_id' => $article->subcategory_id,
            'images' => [$invalidFile],
        ];

        // When: An admin user attempts to update the article with invalid file
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
    }

    /**
     * Test that updating an article changes the correct category and subcategory.
     * This ensures that category/subcategory updates work correctly.
     */
    public function test_can_update_article_category_and_subcategory(): void
    {
        // Given: An existing article and different category/subcategory
        $article = Article::inRandomOrder()->first();
        $this->assertNotNull($article, 'Article not found');

        $newCategory = Category::where('id', '!=', $article->category_id)->first();
        $this->assertNotNull($newCategory, 'Different category not found');

        $newSubcategory = $newCategory->subcategories()->first();
        $this->assertNotNull($newSubcategory, 'Subcategory for new category not found');

        $updateData = [
            'name' => 'Updated Article with New Category',
            'description' => 'Updated description with new category.',
            'price' => 199.99,
            'category_id' => $newCategory->id,
            'subcategory_id' => $newSubcategory->id,
        ];

        // When: An admin user attempts to update the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should succeed
        $response->assertStatus(200);

        // Verify that the article is updated with new category and subcategory
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'category_id' => $newCategory->id,
            'subcategory_id' => $newSubcategory->id,
        ]);

        // Verify the response contains the updated data
        $responseData = $response->json();
        $articleData = $responseData['data']['article'];
        $this->assertEquals($newCategory->id, $articleData['category_id']);
        $this->assertEquals($newSubcategory->id, $articleData['subcategory_id']);
    }
}