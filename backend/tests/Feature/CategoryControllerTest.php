<?php

namespace Tests\Feature;

use App\Enums\UserRoles;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase; // Use RefreshDatabase to reset the database after each test

    /**
     * Set up the test environment.
     * This method seeds the database before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class); // Seed the database with necessary data
    }

    /**
     * Test that an authenticated user can retrieve all categories.
     * This ensures that only authenticated users can access the list of categories.
     */
    public function test_an_authenticated_user_can_retrieve_all_categories(): void
    {
        // Given: An authenticated user and existing categories in the database
        $user = User::role(UserRoles::ADMIN)->first();
        Category::inRandomOrder()->first();

        // When: The user attempts to retrieve all categories
        $response = $this->apiAs($user, 'GET', route('category.index'));

        // Then: The request should succeed, and the response should contain the categories
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'categories' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test that an authenticated user can create a category.
     * This ensures that only authenticated users can create categories successfully.
     */
    public function test_an_authenticated_user_can_create_a_category(): void
    {
        // Given: An authenticated user and valid category data
        $user = User::role(UserRoles::ADMIN)->first();
        $categoryData = [
            'name' => 'New Category',
            'description' => 'This is a new category.',
        ];

        // When: The user attempts to create a category
        $response = $this->apiAs($user, 'POST', route('category.store'), $categoryData);

        // Then: The request should succeed, and the category should be stored in the database
        $response->assertStatus(201);
        $response->assertJsonStructure(['status', 'message', 'data' => ['category']]);
        $this->assertDatabaseHas('categories', $categoryData);
    }

    /**
     * Test that an authenticated user can retrieve a single category.
     * This ensures that only authenticated users can access a specific category.
     */
    public function test_an_authenticated_user_can_retrieve_a_single_category(): void
    {
        // Given: An authenticated user and an existing category
        $user = User::role(UserRoles::ADMIN)->first();
        $category = Category::factory()->create();

        // When: The user attempts to retrieve the category
        $response = $this->apiAs($user, 'GET', route('category.show', $category));

        // Then: The request should succeed, and the response should contain the category
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'category' => [
                    'id',
                    'name',
                    'description',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
        $response->assertJsonFragment([
            'id' => $category->id,
            'name' => $category->name,
            'description' => $category->description,
        ]);
    }

    /**
     * Test that an authenticated user can update a category.
     * This ensures that only authenticated users can update categories successfully.
     */
    public function test_an_authenticated_user_can_update_a_category(): void
    {
        // Given: An authenticated user and an existing category
        $user = User::role(UserRoles::ADMIN)->first();
        $category = Category::inRandomOrder()->first();
        $updateData = [
            'name' => 'Updated Category',
            'description' => 'This is an updated category.',
        ];

        // When: The user attempts to update the category
        $response = $this->apiAs($user, 'PUT', route('category.update', $category), $updateData);

        // Then: The request should succeed, and the category should be updated in the database
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message', 'data' => ['category']]);
        $this->assertDatabaseHas('categories', $updateData);
    }

    /**
     * Test that an authenticated user can delete a category.
     * This ensures that only authenticated users can delete categories successfully.
     */
    public function test_an_authenticated_user_can_delete_a_category(): void
    {
        // Given: An authenticated user and an existing category
        $user = User::role(UserRoles::ADMIN)->first();
        $category = Category::factory()->create();

        // When: The user attempts to delete the category
        $response = $this->apiAs($user, 'DELETE', route('category.destroy', $category));

        // Then: The request should succeed, and the category should be soft deleted in the database
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message']);
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }
}