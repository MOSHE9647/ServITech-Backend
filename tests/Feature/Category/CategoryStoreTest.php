<?php

namespace Tests\Feature\Category;

use App\Enums\UserRoles;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Set up the test environment.
     * This method seeds the database before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * Test that an authenticated admin can create a category.
     * This ensures that only authenticated admin users can create categories successfully.
     */
    public function test_authenticated_admin_can_create_category(): void
    {
        // Given: An authenticated admin user and valid category data
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user should exist in database');

        $categoryData = [
            'name' => 'New Technology Category',
            'description' => 'Category for technology-related articles and content.',
        ];

        // When: The admin attempts to create a category
        $response = $this->apiAs($admin, 'POST', route('category.store'), $categoryData);

        // Then: The request should succeed and category should be stored
        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'category' => [
                        'id',
                        'name',
                        'description',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ])
            ->assertJsonFragment([
                'status' => 201,
                'message' => __('messages.common.created', [
                    'item' => __('messages.entities.category.singular')
                ])
            ])
            ->assertJsonPath('data.category.name', 'New Technology Category')
            ->assertJsonPath('data.category.description', 'Category for technology-related articles and content.');

        // And: The category should exist in the database
        $this->assertDatabaseHas('categories', $categoryData);
    }

    /**
     * Test that category can be created without description.
     * This ensures that description field is optional.
     */
    public function test_category_can_be_created_without_description(): void
    {
        // Given: An admin user and category data without description
        $admin = User::role(UserRoles::ADMIN)->first();
        $categoryData = [
            'name' => 'Category Without Description',
        ];

        // When: The admin creates a category without description
        $response = $this->apiAs($admin, 'POST', route('category.store'), $categoryData);

        // Then: The request should succeed
        $response->assertStatus(201)
            ->assertJsonPath('data.category.name', 'Category Without Description')
            ->assertJsonPath('data.category.description', null);

        // And: The category should exist in the database
        $this->assertDatabaseHas('categories', [
            'name' => 'Category Without Description',
            'description' => null
        ]);
    }

    /**
     * Test that name field is required for category creation.
     * This ensures that missing name field returns validation errors.
     */
    public function test_name_is_required_for_category_creation(): void
    {
        // Given: An admin user and data without name field
        $admin = User::role(UserRoles::ADMIN)->first();
        $categoryData = [
            'description' => 'Description without name',
        ];

        // When: The admin attempts to create category without name
        $response = $this->apiAs($admin, 'POST', route('category.store'), $categoryData);

        // Then: The request should fail with validation error
        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => ['name']
            ])
            ->assertJsonPath('errors.name', __('validation.required', [
                'attribute' => __('validation.attributes.name')
            ]));

        // And: No category should be created in database
        $this->assertDatabaseMissing('categories', ['description' => 'Description without name']);
    }

    /**
     * Test that name must be a string.
     * This ensures that non-string values for name field return validation errors.
     */
    public function test_name_must_be_string(): void
    {
        // Given: An admin user and data with non-string name
        $admin = User::role(UserRoles::ADMIN)->first();
        $categoryData = [
            'name' => 123456,
            'description' => 'Valid description',
        ];

        // When: The admin attempts to create category with invalid name
        $response = $this->apiAs($admin, 'POST', route('category.store'), $categoryData);

        // Then: The request should fail with validation error
        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => ['name']
            ])
            ->assertJsonPath('errors.name', __('validation.string', [
                'attribute' => __('validation.attributes.name')
            ]));
    }

    /**
     * Test that name cannot exceed maximum length.
     * This ensures that excessively long names are rejected.
     */
    public function test_name_cannot_exceed_maximum_length(): void
    {
        // Given: An admin user and data with name exceeding 255 characters
        $admin = User::role(UserRoles::ADMIN)->first();
        $categoryData = [
            'name' => str_repeat('a', 256), // 256 characters
            'description' => 'Valid description',
        ];

        // When: The admin attempts to create category with long name
        $response = $this->apiAs($admin, 'POST', route('category.store'), $categoryData);

        // Then: The request should fail with validation error
        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => ['name']
            ])
            ->assertJsonPath('errors.name', __('validation.max.string', [
                'attribute' => __('validation.attributes.name'),
                'max' => 255
            ]));
    }

    /**
     * Test that description must be a string when provided.
     * This ensures that non-string values for description field return validation errors.
     */
    public function test_description_must_be_string_when_provided(): void
    {
        // Given: An admin user and data with non-string description
        $admin = User::role(UserRoles::ADMIN)->first();
        $categoryData = [
            'name' => 'Valid Category Name',
            'description' => 123456,
        ];

        // When: The admin attempts to create category with invalid description
        $response = $this->apiAs($admin, 'POST', route('category.store'), $categoryData);

        // Then: The request should fail with validation error
        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => ['description']
            ])
            ->assertJsonPath('errors.description', __('validation.string', [
                'attribute' => __('validation.attributes.description')
            ]));
    }

    /**
     * Test that description cannot exceed maximum length.
     * This ensures that excessively long descriptions are rejected.
     */
    public function test_description_cannot_exceed_maximum_length(): void
    {
        // Given: An admin user and data with description exceeding 255 characters
        $admin = User::role(UserRoles::ADMIN)->first();
        $categoryData = [
            'name' => 'Valid Category Name',
            'description' => str_repeat('b', 256), // 256 characters
        ];

        // When: The admin attempts to create category with long description
        $response = $this->apiAs($admin, 'POST', route('category.store'), $categoryData);

        // Then: The request should fail with validation error
        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => ['description']
            ])
            ->assertJsonPath('errors.description', __('validation.max.string', [
                'attribute' => __('validation.attributes.description'),
                'max' => 255
            ]));
    }

    /**
     * Test that unauthenticated users cannot create categories.
     * This ensures that authentication is required for category creation.
     */
    public function test_unauthenticated_user_cannot_create_category(): void
    {
        // Given: Valid category data but no authentication
        $categoryData = [
            'name' => 'Unauthorized Category',
            'description' => 'This should not be created',
        ];

        // When: An unauthenticated user attempts to create category
        $response = $this->postJson(route('category.store'), $categoryData);

        // Then: The response should return a 401 status (Unauthorized)
        $response->assertStatus(401);

        // And: No category should be created
        $this->assertDatabaseMissing('categories', $categoryData);
    }

    /**
     * Test that non-admin users cannot create categories.
     * This ensures that only admin users can create categories.
     */
    public function test_non_admin_user_cannot_create_category(): void
    {
        // Given: A regular user (non-admin) and valid category data
        $user = User::factory()->create();
        $user->assignRole(UserRoles::USER);

        $categoryData = [
            'name' => 'Forbidden Category',
            'description' => 'This should not be created by regular user',
        ];

        // When: The regular user attempts to create category
        $response = $this->apiAs($user, 'POST', route('category.store'), $categoryData);

        // Then: The response should return a 403 status (Forbidden)
        $response->assertStatus(403);

        // And: No category should be created
        $this->assertDatabaseMissing('categories', $categoryData);
    }

    /**
     * Test that category store only accepts POST method.
     * This ensures that only POST requests are allowed for category creation.
     */
    public function test_category_store_only_accepts_post_method(): void
    {
        // Given: An admin user and valid data
        $admin = User::role(UserRoles::ADMIN)->first();
        $categoryData = [
            'name' => 'Test Category',
            'description' => 'Test Description',
        ];

        // When: Attempting to use DELETE method on store endpoint
        $response = $this->apiAs($admin, 'DELETE', route('category.store'), $categoryData);

        // Then: The response should return a 405 status (Method Not Allowed)
        $response->assertStatus(405);
    }

    /**
     * Test that multiple categories can be created successfully.
     * This ensures that the creation process works for multiple categories.
     */
    public function test_multiple_categories_can_be_created(): void
    {
        // Given: An admin user and multiple category data sets
        $admin = User::role(UserRoles::ADMIN)->first();
        $categories = [
            [
                'name' => 'Technology Category',
                'description' => 'For tech articles',
            ],
            [
                'name' => 'Sports Category',
                'description' => 'For sports content',
            ],
            [
                'name' => 'News Category',
                'description' => 'For news articles',
            ]
        ];

        // When: Creating multiple categories
        foreach ($categories as $categoryData) {
            $response = $this->apiAs($admin, 'POST', route('category.store'), $categoryData);
            $response->assertStatus(201);
        }

        // Then: All categories should exist in database
        foreach ($categories as $categoryData) {
            $this->assertDatabaseHas('categories', $categoryData);
        }

        // And: Total count should be correct (plus any seeded categories)
        $totalCategories = Category::count();
        $this->assertGreaterThanOrEqual(3, $totalCategories);
    }
}
