<?php

namespace Tests\Feature\ArticleTests;

use App\Enums\UserRoles;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\ArticleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UpdateArticleTest extends TestCase
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
        // Given: An existing article and a valid update payload
        $article = Article::inRandomOrder()->first();
        $category = Category::inRandomOrder()->first();
        $subcategory = $category->subcategories()->inRandomOrder()->first();

        $updateData = [
            'name' => 'Updated Laptop',
            'description' => 'An updated high-performance laptop with 32GB RAM.',
            'price' => 1500.75,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'images' => [
                UploadedFile::fake()->image('updated-laptop.jpg'),
            ],
        ];

        // When: An admin user attempts to update the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should succeed, and the article should be updated in the database
        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'status', 'message']);
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'name' => $updateData['name'],
            'description' => $updateData['description'],
            'price' => $updateData['price'],
            'category_id' => $updateData['category_id'],
            'subcategory_id' => $updateData['subcategory_id'],
        ]);

        // Verify the new image is stored in the filesystem
        foreach ($updateData['images'] as $image) {
            $imageHashName = $image->hashName();
            Storage::assertExists("articles/$imageHashName");
            Storage::delete("articles/$imageHashName");
        }
    }

    /**
     * Test that an authenticated non-admin user cannot update an article.
     * This ensures that only admin users can update articles.
     */
    public function test_an_authenticated_non_admin_user_cannot_update_an_article(): void
    {
        // Given: An existing article and a valid update payload
        $article = Article::inRandomOrder()->first();
        $updateData = [
            'name' => 'Updated Laptop',
            'description' => 'An updated high-performance laptop with 32GB RAM.',
            'price' => 1500.75,
        ];

        // When: A non-admin user attempts to update the article
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should fail with a 403 Forbidden status
        $response->assertStatus(403);
        $response->assertJsonStructure(['status', 'message', 'errors']);
    }

    /**
     * Test that a non-authenticated user cannot update an article.
     * This ensures that only authenticated users can update articles.
     */
    public function test_a_non_authenticated_user_cannot_update_an_article(): void
    {
        // Given: An existing article and a valid update payload
        $article = Article::inRandomOrder()->first();
        $updateData = [
            'name' => 'Updated Laptop',
            'description' => 'An updated high-performance laptop with 32GB RAM.',
            'price' => 1500.75,
        ];

        // When: A non-authenticated user attempts to update the article
        $response = $this->putJson(route('articles.update', $article), $updateData);

        // Then: The request should fail with a 401 Unauthorized status
        $response->assertStatus(401);
        $response->assertJsonStructure(['status', 'message']);
    }

    /**
     * Test that the name field is required when updating an article.
     * This ensures that missing the name field returns a 422 status with validation errors.
     */
    public function test_name_must_be_required_when_updating(): void
    {
        // Given: An existing article and an update payload with a missing name
        $article = Article::inRandomOrder()->first();
        $updateData = [
            'description' => 'An updated high-performance laptop with 32GB RAM.',
            'price' => 1500.75,
            'category_id' => 1,
            'subcategory_id' => 2,
        ];

        // When: An admin user attempts to update the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['name']]);
    }

    /**
     * Test that the name must be a string when updating an article.
     * This ensures that non-string values for the name field return a 422 status with validation errors.
     */
    public function test_name_must_be_a_string_when_updating(): void
    {
        // Given: An existing article and an update payload with a non-string name
        $article = Article::inRandomOrder()->first();
        $updateData = [
            'name' => 12345,
            'description' => 'An updated high-performance laptop with 32GB RAM.',
            'price' => 1500.75,
            'category_id' => 1,
            'subcategory_id' => 2,
        ];

        // When: An admin user attempts to update the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['name']]);
    }

    /**
     * Test that the name must have at least 3 characters when updating an article.
     * This ensures that short names return a 422 status with validation errors.
     */
    public function test_name_must_have_at_least_3_characters_when_updating(): void
    {
        // Given: An existing article and an update payload with a name that has less than 3 characters
        $article = Article::inRandomOrder()->first();
        $updateData = [
            'name' => 'AB',
            'description' => 'An updated high-performance laptop with 32GB RAM.',
            'price' => 1500.75,
            'category_id' => 1,
            'subcategory_id' => 2,
        ];

        // When: An admin user attempts to update the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['name']]);
    }

    /**
     * Test that the description field is required when updating an article.
     * This ensures that missing the description field returns a 422 status with validation errors.
     */
    public function test_description_must_be_required_when_updating(): void
    {
        // Given: An existing article and an update payload with a missing description
        $article = Article::inRandomOrder()->first();
        $updateData = [
            'name' => 'Updated Laptop',
            'price' => 1500.75,
            'category_id' => 1,
            'subcategory_id' => 2,
        ];

        // When: An admin user attempts to update the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['description']]);
    }

    /**
     * Test that the description must be a string when updating an article.
     * This ensures that non-string values for the description field return a 422 status with validation errors.
     */
    public function test_description_must_be_a_string_when_updating(): void
    {
        // Given: An existing article and an update payload with a non-string description
        $article = Article::inRandomOrder()->first();
        $updateData = [
            'name' => 'Updated Laptop',
            'description' => 1234567890,
            'price' => 1500.75,
            'category_id' => 1,
            'subcategory_id' => 2,
        ];

        // When: An admin user attempts to update the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['description']]);
    }

    /**
     * Test that the description must have at least 10 characters when updating an article.
     * This ensures that short descriptions return a 422 status with validation errors.
     */
    public function test_description_must_have_at_least_10_characters_when_updating(): void
    {
        // Given: An existing article and an update payload with a description that has less than 10 characters
        $article = Article::inRandomOrder()->first();
        $updateData = [
            'name' => 'Updated Laptop',
            'description' => 'Short',
            'price' => 1500.75,
            'category_id' => 1,
            'subcategory_id' => 2,
        ];

        // When: An admin user attempts to update the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['description']]);
    }

    /**
     * Test that the description must not exceed 255 characters when updating an article.
     * This ensures that long descriptions return a 422 status with validation errors.
     */
    public function test_description_must_not_exceed_255_characters_when_updating(): void
    {
        // Given: An existing article and an update payload with a description that exceeds 255 characters
        $article = Article::inRandomOrder()->first();
        $updateData = [
            'name' => 'Updated Laptop',
            'description' => str_repeat('A', 256), // 256 characters
            'price' => 1500.75,
            'category_id' => 1,
            'subcategory_id' => 2,
        ];

        // When: An admin user attempts to update the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['description']]);
    }

    /**
     * Test that the price field is required when updating an article.
     * This ensures that missing the price field returns a 422 status with validation errors.
     */
    public function test_price_must_be_required_when_updating(): void
    {
        // Given: An existing article and an update payload with a missing price
        $article = Article::inRandomOrder()->first();
        $updateData = [
            'name' => 'Updated Laptop',
            'description' => 'An updated high-performance laptop with 32GB RAM.',
        ];

        // When: An admin user attempts to update the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['price']]);
    }

    /**
     * Test that the price must be numeric when updating an article.
     * This ensures that non-numeric values for the price field return a 422 status with validation errors.
     */
    public function test_price_must_be_numeric_when_updating(): void
    {
        // Given: An existing article and an update payload with a non-numeric price
        $article = Article::inRandomOrder()->first();
        $updateData = [
            'name' => 'Updated Laptop',
            'description' => 'An updated high-performance laptop with 32GB RAM.',
            'price' => 'not-a-number',
            'category_id' => 1,
            'subcategory_id' => 2,
        ];

        // When: An admin user attempts to update the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['price']]);
    }

    /**
     * Test that the price must be at least 0 when updating an article.
     * This ensures that negative prices return a 422 status with validation errors.
     */
    public function test_price_must_be_at_least_0_when_updating(): void
    {
        // Given: An existing article and an update payload with a negative price
        $article = Article::inRandomOrder()->first();
        $updateData = [
            'name' => 'Updated Laptop',
            'description' => 'An updated high-performance laptop with 32GB RAM.',
            'price' => -100,
            'category_id' => 1,
            'subcategory_id' => 2,
        ];

        // When: An admin user attempts to update the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['price']]);
    }

    /**
     * Test that the category_id field is required when updating an article.
     * This ensures that missing the category_id field returns a 422 status with validation errors.
     */
    public function test_category_id_must_be_required_when_updating(): void
    {
        // Given: An existing article and an update payload with a missing category_id
        $article = Article::inRandomOrder()->first();
        $updateData = [
            'name' => 'Updated Laptop',
            'description' => 'An updated high-performance laptop with 32GB RAM.',
            'price' => 1500.75,
            'subcategory_id' => 2,
        ];

        // When: An admin user attempts to update the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['category_id']]);
    }

    /**
     * Test that the category_id must exist when updating an article.
     * This ensures that invalid category IDs return a 422 status with validation errors.
     */
    public function test_category_id_must_exist_when_updating(): void
    {
        // Given: An existing article and an update payload with an invalid category_id
        $article = Article::inRandomOrder()->first();
        $updateData = [
            'name' => 'Updated Laptop',
            'description' => 'An updated high-performance laptop with 32GB RAM.',
            'price' => 1500.75,
            'category_id' => 999, // Non-existent category ID
        ];

        // When: An admin user attempts to update the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['category_id']]);
    }

    /**
     * Test that the subcategory_id field is required when updating an article.
     * This ensures that missing the subcategory_id field returns a 422 status with validation errors.
     */
    public function test_subcategory_id_must_be_required_when_updating(): void
    {
        // Given: An existing article and an update payload with a missing subcategory_id
        $article = Article::inRandomOrder()->first();
        $updateData = [
            'name' => 'Updated Laptop',
            'description' => 'An updated high-performance laptop with 32GB RAM.',
            'price' => 1500.75,
            'category_id' => 1,
        ];

        // When: An admin user attempts to update the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['subcategory_id']]);
    }

    /**
     * Test that the images field must be an array when updating an article.
     * This ensures that non-array values for the images field return a 422 status with validation errors.
     */
    public function test_images_must_be_an_array_when_updating(): void
    {
        // Given: An existing article and an update payload with a non-array images field
        $article = Article::inRandomOrder()->first();
        $updateData = [
            'name' => 'Updated Laptop',
            'description' => 'An updated high-performance laptop with 32GB RAM.',
            'price' => 1500.75,
            'category_id' => 1,
            'subcategory_id' => 2,
            'images' => 'not-an-array',
        ];

        // When: An admin user attempts to update the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['images']]);
    }

    /**
     * Test that each image must be a valid image file when updating an article.
     * This ensures that invalid image files return a 422 status with validation errors.
     */
    public function test_each_image_must_be_a_valid_image_file_when_updating(): void
    {
        // Given: An existing article and an update payload with an invalid image file
        $article = Article::inRandomOrder()->first();
        $updateData = [
            'name' => 'Updated Laptop',
            'description' => 'An updated high-performance laptop with 32GB RAM.',
            'price' => 1500.75,
            'images' => [
                UploadedFile::fake()->create('not-an-image.txt', 100), // Invalid file type
            ],
        ];

        // When: An admin user attempts to update the article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'PUT', route('articles.update', $article), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['images.0']]);
    }
}