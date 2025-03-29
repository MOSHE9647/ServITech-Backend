<?php

namespace Tests\Feature\ArticleTests;

use App\Enums\UserRoles;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\ArticleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CreateArticleTest extends TestCase
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
     * Test that an authenticated admin user can create an article.
     * This ensures that only admin users can create articles successfully.
     */
    public function test_an_authenticated_admin_user_can_create_an_article(): void
    {
        // Given: A valid article payload with a random category and subcategory
        $category = Category::inRandomOrder()->first();
        $subcategory = $category->subcategories()->inRandomOrder()->first();
        if (!$category || !$subcategory) {
            // Fail the test if no categories or subcategories exist
            $this->fail('No categories or subcategories found.');
        }

        $articleData = [
            'name'              => 'Laptop',
            'description'       => 'A high-performance laptop with 16GB RAM.',
            'price'             => 1200.50,
            'category_id'       => $category->id, // Get the random category ID
            'subcategory_id'    => $subcategory->id, // Get the random subcategory ID
            'images'            => [
                UploadedFile::fake()->image('laptop.jpg'),
                UploadedFile::fake()->image('laptop2.jpg'),
            ],
        ];

        // When: An admin user attempts to create an article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'POST', route('articles.store'), $articleData);
        // dd($response->json());

        // Then: The request should succeed, and the article should be stored in the database
        $response->assertStatus(201);
        $response->assertJsonStructure(['data', 'status', 'message']);
        // Verify the article data exists in the articles table
        $this->assertDatabaseHas('articles', [
            'name'           => $articleData['name'],
            'description'    => $articleData['description'],
            'price'          => $articleData['price'],
            'category_id'    => $articleData['category_id'],
            'subcategory_id' => $articleData['subcategory_id'],
        ]);

        // Verify each image is stored in the images table
        foreach ($articleData['images'] as $image) {
            $imageHashName = $image->hashName();

            // Check if the image is stored in the public/storage/articles directory
            $this->assertDatabaseHas('images', [
                'path' => Storage::url("articles/$imageHashName"),
            ]);

            // Check if the image is stored in the filesystem and delete it
            Storage::assertExists("articles/$imageHashName");
            Storage::delete("articles/$imageHashName");
        }
    }

    /**
     * Test that an authenticated non-admin user cannot create an article.
     * This ensures that only admin users can create articles.
     */
    public function test_an_authenticated_non_admin_user_cannot_create_an_article(): void
    {
        // Given: A valid article payload
        $articleData = [
            'name' => 'Laptop',
            'description' => 'A high-performance laptop with 16GB RAM.',
            'price' => 1200.50,
            'category_id' => 1,
            'subcategory_id' => 2,
        ];

        // When: A non-admin user attempts to create an article
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('articles.store'), $articleData);
        // dd($response->json());

        // Then: The request should fail with a 403 Forbidden status
        $response->assertStatus(403);
        $response->assertJsonStructure(['status', 'message', 'errors']);

        $response->assertJsonFragment([
            'status' => 403,
            'message' => __("User does not have the right roles."),
        ]);
    }

    /**
     * Test that a non-authenticated user cannot create an article.
     * This ensures that only authenticated users can create articles.
     */
    public function test_a_non_authenticated_user_cannot_create_an_article(): void
    {
        // Given: A valid article payload
        $articleData = [
            'name' => 'Laptop',
            'description' => 'A high-performance laptop with 16GB RAM.',
            'price' => 1200.50,
            'category_id' => 1,
            'subcategory_id' => 2,
        ];

        // When: A non-authenticated user attempts to create an article
        $response = $this->postJson(route('articles.store'), $articleData);

        // Then: The request should fail with a 401 Unauthorized status
        $response->assertStatus(401);
        $response->assertJsonStructure(['status', 'message']);
        $response->assertJsonFragment([
            'status' => 401,
            'message' => __('auth.unauthenticated'),
        ]);

        // Verify that the article was not created in the database
        $this->assertDatabaseMissing('articles', $articleData);
    }

    /**
     * Test that the name field is required.
     * This ensures that missing the name field returns a 422 status with validation errors.
     */
    public function test_name_must_be_required(): void
    {
        // Given: An article payload with a missing name
        $articleData = [
            'description' => 'A high-performance laptop with 16GB RAM.',
            'price' => 1200.50,
            'category_id' => 1,
            'subcategory_id' => 2,
        ];

        // When: An admin user attempts to create an article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'POST', route('articles.store'), $articleData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['name']]);

        $response->assertJsonFragment([
            'status' => 422,
            'message' => __('validation.required', [
                'attribute' => __('validation.attributes.name'),
            ]),
        ]);
    }

    /**
     * Test that the name must be a string.
     * This ensures that non-string values for the name field return a 422 status with validation errors.
     */
    public function test_name_must_be_a_string(): void
    {
        // Given: An article payload with a non-string name
        $articleData = [
            'name' => 12345,
            'description' => 'A high-performance laptop with 16GB RAM.',
            'price' => 1200.50,
            'category_id' => 1,
            'subcategory_id' => 2,
        ];

        // When: An admin user attempts to create an article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'POST', route('articles.store'), $articleData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['name']]);

        $response->assertJsonFragment([
            'status' => 422,
            'message' => __('validation.string', [
                'attribute' => __('validation.attributes.name'),
            ]),
        ]);
    }

    /**
     * Test that the name must have at least 3 characters.
     * This ensures that short names return a 422 status with validation errors.
     */
    public function test_name_must_have_at_least_3_characters(): void
    {
        // Given: An article payload with a name that has less than 3 characters
        $articleData = [
            'name' => 'AB',
            'description' => 'A high-performance laptop with 16GB RAM.',
            'price' => 1200.50,
            'category_id' => 1,
            'subcategory_id' => 2,
        ];

        // When: An admin user attempts to create an article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'POST', route('articles.store'), $articleData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['name']]);

        $response->assertJsonFragment([
            'status' => 422,
            'message' => __('validation.min.string', [
                'attribute' => __('validation.attributes.name'),
                'min' => 3,
            ]),
        ]);
    }

    /**
     * Test that the description field is required.
     * This ensures that missing the description field returns a 422 status with validation errors.
     */
    public function test_description_must_be_required(): void
    {
        // Given: An article payload with a missing description
        $articleData = [
            'name' => 'Laptop',
            'price' => 1200.50,
            'category_id' => 1,
            'subcategory_id' => 2,
        ];

        // When: An admin user attempts to create an article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'POST', route('articles.store'), $articleData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['description']]);

        $response->assertJsonFragment([
            'status' => 422,
            'message' => __('validation.required', [
                'attribute' => __('validation.attributes.description'),
            ]),
        ]);
    }

    /**
     * Test that the description must be a string.
     * This ensures that non-string values for the description field return a 422 status with validation errors.
     */
    public function test_description_must_be_a_string(): void
    {
        // Given: An article payload with a non-string description
        $articleData = [
            'name' => 'Laptop',
            'description' => 1234567890,
            'price' => 1200.50,
            'category_id' => 1,
            'subcategory_id' => 2,
        ];

        // When: An admin user attempts to create an article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists
        
        $response = $this->apiAs($admin, 'POST', route('articles.store'), $articleData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['description']]);

        $response->assertJsonFragment([
            'status' => 422,
            'message' => __('validation.string', [
                'attribute' => __('validation.attributes.description'),
            ]),
        ]);
    }

    /* 
     * Test that the description must have at least 10 characters.
     * This ensures that short descriptions return a 422 status with validation errors.
     */
    public function test_description_must_have_at_least_10_caracters(): void
    {
        // Given: An article payload with a description that has less than 10 characters
        $articleData = [
            'name' => 'Laptop',
            'description' => 'Short',
            'price' => 1200.50,
            'category_id' => 1,
            'subcategory_id' => 2,
        ];

        // When: An admin user attempts to create an article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'POST', route('articles.store'), $articleData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['description']]);

        $response->assertJsonFragment([
            'status' => 422,
            'message' => __('validation.min.string', [
                'attribute' => __('validation.attributes.description'),
                'min' => 10,
            ]),
        ]);
    }

    /**
     * Test that the description must not exceed 255 characters.
     * This ensures that long descriptions return a 422 status with validation errors.
     */
    public function test_description_must_not_exceed_255_characters(): void
    {
        // Given: An article payload with a description that exceeds 255 characters
        $articleData = [
            'name' => 'Laptop',
            'description' => str_repeat('A', 256), // 256 characters
            'price' => 1200.50,
            'category_id' => 1,
            'subcategory_id' => 2,
        ];

        // When: An admin user attempts to create an article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists
        
        $response = $this->apiAs($admin, 'POST', route('articles.store'), $articleData);
        
        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['description']]);

        $response->assertJsonFragment([
            'status' => 422,
            'message' => __('validation.max.string', [
                'attribute' => __('validation.attributes.description'),
                'max' => 255,
            ]),
        ]);
    }

    /**
     * Test that the price field is required.
     * This ensures that missing the price field returns a 422 status with validation errors.
     */
    public function test_price_must_be_required(): void
    {
        // Given: An article payload with a missing price
        $articleData = [
            'name' => 'Laptop',
            'description' => 'A high-performance laptop with 16GB RAM.',
            'category_id' => 1,
            'subcategory_id' => 2,
        ];

        // When: An admin user attempts to create an article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'POST', route('articles.store'), $articleData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['price']]);

        $response->assertJsonFragment([
            'status' => 422,
            'message' => __('validation.required', [
                'attribute' => __('validation.attributes.price'),
            ]),
        ]);
    }

    /**
     * Test that the price must be numeric.
     * This ensures that non-numeric values for the price field return a 422 status with validation errors.
     */
    public function test_price_must_be_numeric(): void
    {
        // Given: An article payload with a non-numeric price
        $articleData = [
            'name' => 'Laptop',
            'description' => 'A high-performance laptop with 16GB RAM.',
            'price' => 'not-a-number',
            'category_id' => 1,
            'subcategory_id' => 2,
        ];

        // When: An admin user attempts to create an article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'POST', route('articles.store'), $articleData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['price']]);

        $response->assertJsonFragment([
            'status' => 422,
            'message' => __('validation.numeric', [
                'attribute' => __('validation.attributes.price'),
            ]),
        ]);
    }

    /**
     * Test that the price must be at least 0.
     * This ensures that negative prices return a 422 status with validation errors.
     */
    public function test_price_must_be_at_least_0(): void
    {
        // Given: An article payload with a negative price
        $articleData = [
            'name' => 'Laptop',
            'description' => 'A high-performance laptop with 16GB RAM.',
            'price' => -100,
            'category_id' => 1,
            'subcategory_id' => 2,
        ];

        // When: An admin user attempts to create an article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'POST', route('articles.store'), $articleData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['price']]);

        $response->assertJsonFragment([
            'status' => 422,
            'message' => __('validation.min.numeric', [
                'attribute' => __('validation.attributes.price'),
                'min' => 0,
            ]),
        ]);
    }

    /**
     * Test that the category_id field is required.
     * This ensures that missing the category_id field returns a 422 status with validation errors.
     */
    public function test_category_id_must_be_required(): void
    {
        // Given: An article payload with a missing category_id
        $articleData = [
            'name' => 'Laptop',
            'description' => 'A high-performance laptop with 16GB RAM.',
            'price' => 1200.50,
            'subcategory_id' => 2,
        ];

        // When: An admin user attempts to create an article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'POST', route('articles.store'), $articleData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['category_id']]);

        $response->assertJsonFragment([
            'status' => 422,
            'message' => __('validation.required', [
                'attribute' => __('validation.attributes.category'),
            ]),
        ]);
    }

    /**
     * Test that the category_id must exist in the categories table.
     * This ensures that invalid category IDs return a 422 status with validation errors.
     */
    public function test_category_id_must_exist(): void
    {
        // Given: An article payload with an invalid category_id
        $articleData = [
            'name' => 'Laptop',
            'description' => 'A high-performance laptop with 16GB RAM.',
            'price' => 1200.50,
            'category_id' => 999, // Non-existent category ID
            'subcategory_id' => 2,
        ];

        // When: An admin user attempts to create an article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'POST', route('articles.store'), $articleData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['category_id']]);

        $response->assertJsonFragment([
            'status' => 422,
            'message' => __('validation.exists', [
                'attribute' => __('validation.attributes.category'),
            ]),
        ]);
    }

    /**
     * Test that the subcategory_id field is required.
     * This ensures that missing the subcategory_id field returns a 422 status with validation errors.
     */
    public function test_subcategory_id_must_be_required(): void
    {
        // Given: An article payload with a missing subcategory_id
        $articleData = [
            'name' => 'Laptop',
            'description' => 'A high-performance laptop with 16GB RAM.',
            'price' => 1200.50,
            'category_id' => 1,
        ];

        // When: An admin user attempts to create an article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'POST', route('articles.store'), $articleData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['subcategory_id']]);

        $response->assertJsonFragment([
            'status' => 422,
            'message' => __('validation.required', [
                'attribute' => __('validation.attributes.subcategory'),
            ]),
        ]);
    }

    /**
     * Test that the subcategory_id must exist in the subcategories table.
     * This ensures that invalid subcategory IDs return a 422 status with validation errors.
     */
    public function test_subcategory_id_must_exist(): void
    {
        // Given: An article payload with an invalid subcategory_id
        $articleData = [
            'name' => 'Laptop',
            'description' => 'A high-performance laptop with 16GB RAM.',
            'price' => 1200.50,
            'category_id' => 1,
            'subcategory_id' => 999, // Non-existent subcategory ID
        ];

        // When: An admin user attempts to create an article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'POST', route('articles.store'), $articleData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['subcategory_id']]);

        $response->assertJsonFragment([
            'status' => 422,
            'message' => __('validation.exists', [
                'attribute' => __('validation.attributes.subcategory'),
            ]),
        ]);
    }

    /**
     * Test that the images field must be an array.
     * This ensures that non-array values for the images field return a 422 status with validation errors.
     */
    public function test_images_must_be_an_array(): void
    {
        // Given: An article payload with a non-array images field
        $articleData = [
            'name' => 'Laptop',
            'description' => 'A high-performance laptop with 16GB RAM.',
            'price' => 1200.50,
            'category_id' => 1,
            'subcategory_id' => 2,
            'images' => 'not-an-array',
        ];

        // When: An admin user attempts to create an article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'POST', route('articles.store'), $articleData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['images']]);

        $response->assertJsonFragment([
            'status' => 422,
            'message' => __('validation.array', [
                'attribute' => __('validation.attributes.images'),
            ]),
        ]);
    }

    /**
     * Test that each image must be a valid image file.
     * This ensures that invalid image files return a 422 status with validation errors.
     */
    public function test_each_image_must_be_a_valid_image_file(): void
    {
        // Given: An article payload with an invalid image file
        $articleData = [
            'name' => 'Laptop',
            'description' => 'A high-performance laptop with 16GB RAM.',
            'price' => 1200.50,
            'category_id' => 1,
            'subcategory_id' => 2,
            'images' => [
                UploadedFile::fake()->create('not-an-image.txt', 100), // Invalid file type
            ],
        ];

        // When: An admin user attempts to create an article
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, __('messages.user.not_found')); // Ensure the admin exists

        $response = $this->apiAs($admin, 'POST', route('articles.store'), $articleData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['images.0']]);

        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'images.0' => [
                    __('validation.mimes', [
                        'attribute' => __('validation.attributes.image'),
                        'values' => 'jpeg,png,jpg',
                    ]),
                    __('validation.image', [
                        'attribute' => __('validation.attributes.image'),
                    ]),
                ],
            ],
        ]);
    }
}
