<?php

namespace Tests\Feature\Category;

use App\Enums\UserRoles;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryIndexTest extends TestCase
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
     * Test that an authenticated admin can retrieve all categories.
     * This ensures that only authenticated admin users can access the list of categories.
     */
    public function test_authenticated_admin_can_retrieve_all_categories(): void
    {
        // Given: An authenticated admin user and existing categories
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user should exist in database');

        // Create some test categories
        $categories = Category::factory()->count(3)->create();

        // When: The admin attempts to retrieve all categories
        $response = $this->apiAs($admin, 'GET', route('category.index'));

        // Then: The request should succeed with proper structure
        $response->assertStatus(200)
            ->assertJsonStructure([
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
            ])
            ->assertJsonFragment([
                'status' => 200,
                'message' => __('messages.common.retrieved_all', [
                    'items' => __('messages.entities.category.plural')
                ])
            ]);

        // And: Verify the categories are ordered by ID descending
        $responseCategories = $response->json('data.categories');
        $this->assertGreaterThanOrEqual(3, count($responseCategories));
        
        // Verify ordering (first should have highest ID)
        if (count($responseCategories) > 1) {
            $this->assertGreaterThan(
                $responseCategories[1]['id'],
                $responseCategories[0]['id']
            );
        }
    }

    /**
     * Test that unauthenticated users cannot access categories.
     * This ensures that authentication is required for category access.
     */
    public function test_unauthenticated_user_cannot_access_categories(): void
    {
        // When: An unauthenticated user attempts to access categories
        $response = $this->getJson(route('category.index'));

        // Then: The response should return a 401 status (Unauthorized)
        $response->assertStatus(401);
    }

    /**
     * Test that non-admin users cannot access categories.
     * This ensures that only admin users can access categories.
     */
    public function test_non_admin_user_cannot_access_categories(): void
    {
        // Given: A regular user (non-admin)
        $user = User::factory()->create();
        $user->assignRole(UserRoles::USER);

        // When: The user attempts to access categories
        $response = $this->apiAs($user, 'GET', route('category.index'));

        // Then: The response should return a 403 status (Forbidden)
        $response->assertStatus(403);
    }

    /**
     * Test that categories index works when no categories exist.
     * This ensures that the endpoint handles empty results gracefully.
     */
    public function test_categories_index_with_no_categories(): void
    {
        // Given: An admin user and no categories in database
        $admin = User::role(UserRoles::ADMIN)->first();
        Category::query()->delete(); // Remove all categories

        // When: The admin attempts to retrieve categories
        $response = $this->apiAs($admin, 'GET', route('category.index'));

        // Then: The request should succeed with empty categories array
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['categories']
            ])
            ->assertJsonPath('data.categories', []);
    }

    /**
     * Test that categories index only accepts GET method.
     * This ensures that only GET requests are allowed for categories listing.
     */
    public function test_categories_index_only_accepts_get_method(): void
    {
        // Given: An admin user
        $admin = User::role(UserRoles::ADMIN)->first();

        // When: Attempting to use PUT method on index endpoint
        $response = $this->apiAs($admin, 'PUT', route('category.index'));

        // Then: The response should return a 405 status (Method Not Allowed)
        $response->assertStatus(405);
    }

    /**
     * Test that categories are returned with complete data structure.
     * This ensures that all necessary category fields are included.
     */
    public function test_categories_returned_with_complete_data(): void
    {
        // Given: An admin user and a category with all fields
        $admin = User::role(UserRoles::ADMIN)->first();
        $category = Category::factory()->create([
            'name' => 'Test Category',
            'description' => 'Test Description'
        ]);

        // When: The admin retrieves categories
        $response = $this->apiAs($admin, 'GET', route('category.index'));

        // Then: The category data should include all expected fields
        $response->assertStatus(200);
        
        $categories = $response->json('data.categories');
        $testCategory = collect($categories)->firstWhere('id', $category->id);
        
        $this->assertNotNull($testCategory);
        $this->assertEquals($category->name, $testCategory['name']);
        $this->assertEquals($category->description, $testCategory['description']);
        $this->assertArrayHasKey('created_at', $testCategory);
        $this->assertArrayHasKey('updated_at', $testCategory);
    }

    /**
     * Test that categories list includes soft deleted status information.
     * This ensures that soft deletion is properly handled.
     */
    public function test_categories_list_excludes_soft_deleted_categories(): void
    {
        // Given: An admin user and categories (some soft deleted)
        $admin = User::role(UserRoles::ADMIN)->first();
        $activeCategory = Category::factory()->create(['name' => 'Active Category']);
        $deletedCategory = Category::factory()->create(['name' => 'Deleted Category']);
        
        // Soft delete one category
        $deletedCategory->delete();

        // When: The admin retrieves categories
        $response = $this->apiAs($admin, 'GET', route('category.index'));

        // Then: Only active categories should be returned
        $response->assertStatus(200);
        
        $categories = $response->json('data.categories');
        $categoryNames = collect($categories)->pluck('name')->toArray();
        
        $this->assertContains('Active Category', $categoryNames);
        $this->assertNotContains('Deleted Category', $categoryNames);
    }
}
