<?php

namespace Tests\Feature\Category;

use App\Enums\UserRoles;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryShowTest extends TestCase
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
     * Test that an authenticated admin can retrieve a single category.
     * This ensures that only authenticated admin users can access specific category details.
     */
    public function test_authenticated_admin_can_retrieve_single_category(): void
    {
        // Given: An authenticated admin user and an existing category
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user should exist in database');

        $category = Category::factory()->create([
            'name' => 'Technology Articles',
            'description' => 'All articles related to technology and innovation'
        ]);

        // When: The admin attempts to retrieve the specific category
        $response = $this->apiAs($admin, 'GET', route('category.show', $category->name));

        // Then: The request should succeed with proper structure
        $response->assertStatus(200)
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
                    ],
                ],
            ])
            ->assertJsonFragment([
                'status' => 200,
                'message' => __('messages.common.retrieved', [
                    'item' => __('messages.entities.category.singular')
                ])
            ])
            ->assertJsonPath('data.category.id', $category->id)
            ->assertJsonPath('data.category.name', 'Technology Articles')
            ->assertJsonPath('data.category.description', 'All articles related to technology and innovation');
    }

    /**
     * Test that retrieving non-existent category returns 404.
     * This ensures that proper error handling for missing categories.
     */
    public function test_retrieving_non_existent_category_returns_404(): void
    {
        // Given: An authenticated admin user and a non-existent category name
        $admin = User::role(UserRoles::ADMIN)->first();
        $nonExistentCategoryName = 'non-existent-category-name';

        // Ensure the category doesn't exist
        $this->assertDatabaseMissing('categories', ['name' => $nonExistentCategoryName]);

        // When: The admin attempts to retrieve non-existent category
        $response = $this->apiAs($admin, 'GET', route('category.show', $nonExistentCategoryName));

        // Then: The request should return 404 error
        $response->assertStatus(404);
    }

    /**
     * Test that unauthenticated users cannot access category details.
     * This ensures that authentication is required for category access.
     */
    public function test_unauthenticated_user_cannot_access_category_details(): void
    {
        // Given: An existing category
        $category = Category::factory()->create();

        // When: An unauthenticated user attempts to access category details
        $response = $this->getJson(route('category.show', $category->name));

        // Then: The response should return a 401 status (Unauthorized)
        $response->assertStatus(401);
    }

    /**
     * Test that non-admin users cannot access category details.
     * This ensures that only admin users can access category details.
     */
    public function test_non_admin_user_cannot_access_category_details(): void
    {
        // Given: A regular user (non-admin) and an existing category
        $user = User::factory()->create();
        $user->assignRole(UserRoles::USER);

        $category = Category::factory()->create();

        // When: The regular user attempts to access category details
        $response = $this->apiAs($user, 'GET', route('category.show', $category->name));

        // Then: The response should return a 403 status (Forbidden)
        $response->assertStatus(403);
    }

    /**
     * Test that category show endpoint only accepts GET method.
     * This ensures that only GET requests are allowed for category retrieval.
     */
    public function test_category_show_only_accepts_get_method(): void
    {
        // Given: An admin user and an existing category
        $admin = User::role(UserRoles::ADMIN)->first();
        $category = Category::factory()->create();

        // When: Attempting to use POST method on show endpoint
        $response = $this->apiAs($admin, 'POST', route('category.show', $category->name));

        // Then: The response should return a 405 status (Method Not Allowed)
        $response->assertStatus(405);
    }

    /**
     * Test that category with null description is handled properly.
     * This ensures that categories without descriptions are displayed correctly.
     */
    public function test_category_with_null_description_handled_properly(): void
    {
        // Given: An admin user and a category without description
        $admin = User::role(UserRoles::ADMIN)->first();
        $category = Category::factory()->create([
            'name' => 'Category Without Description',
            'description' => null
        ]);

        // When: The admin retrieves the category
        $response = $this->apiAs($admin, 'GET', route('category.show', $category->name));

        // Then: The request should succeed with null description
        $response->assertStatus(200)
            ->assertJsonPath('data.category.name', 'Category Without Description')
            ->assertJsonPath('data.category.description', null);
    }

    /**
     * Test that category show returns complete timestamp information.
     * This ensures that created_at and updated_at are properly included.
     */
    public function test_category_show_returns_complete_timestamp_info(): void
    {
        // Given: An admin user and a category
        $admin = User::role(UserRoles::ADMIN)->first();
        $category = Category::factory()->create();

        // When: The admin retrieves the category
        $response = $this->apiAs($admin, 'GET', route('category.show', $category->name));

        // Then: The response should include timestamp information
        $response->assertStatus(200);
        
        $categoryData = $response->json('data.category');
        $this->assertArrayHasKey('created_at', $categoryData);
        $this->assertArrayHasKey('updated_at', $categoryData);
        $this->assertNotNull($categoryData['created_at']);
        $this->assertNotNull($categoryData['updated_at']);
    }

    /**
     * Test that soft deleted categories cannot be retrieved.
     * This ensures that deleted categories return 404.
     */
    public function test_soft_deleted_category_cannot_be_retrieved(): void
    {
        // Given: An admin user and a soft deleted category
        $admin = User::role(UserRoles::ADMIN)->first();
        $category = Category::factory()->create([
            'name' => 'deleted-category'
        ]);
        
        // Soft delete the category
        $category->delete();

        // When: The admin attempts to retrieve the deleted category
        $response = $this->apiAs($admin, 'GET', route('category.show', 'deleted-category'));

        // Then: The request should return 404
        $response->assertStatus(404);
    }

    /**
     * Test that category name route binding works correctly.
     * This ensures that route model binding by name functions properly.
     */
    public function test_category_name_route_binding_works_correctly(): void
    {
        // Given: An admin user and categories with different names
        $admin = User::role(UserRoles::ADMIN)->first();
        
        $category1 = Category::factory()->create(['name' => 'technology']);
        $category2 = Category::factory()->create(['name' => 'sports']);

        // When: Retrieving each category by name
        $response1 = $this->apiAs($admin, 'GET', route('category.show', 'technology'));
        $response2 = $this->apiAs($admin, 'GET', route('category.show', 'sports'));

        // Then: Each request should return the correct category
        $response1->assertStatus(200)
            ->assertJsonPath('data.category.id', $category1->id)
            ->assertJsonPath('data.category.name', 'technology');

        $response2->assertStatus(200)
            ->assertJsonPath('data.category.id', $category2->id)
            ->assertJsonPath('data.category.name', 'sports');
    }

    /**
     * Test that category show handles special characters in names.
     * This ensures that categories with special characters can be retrieved.
     */
    public function test_category_show_handles_special_characters_in_names(): void
    {
        // Given: An admin user and a category with special characters
        $admin = User::role(UserRoles::ADMIN)->first();
        $category = Category::factory()->create([
            'name' => 'Technology & Innovation',
            'description' => 'Articles about tech & innovation'
        ]);

        // When: The admin retrieves the category with special characters
        $response = $this->apiAs($admin, 'GET', route('category.show', 'Technology & Innovation'));

        // Then: The request should succeed
        $response->assertStatus(200)
            ->assertJsonPath('data.category.name', 'Technology & Innovation')
            ->assertJsonPath('data.category.description', 'Articles about tech & innovation');
    }
}
