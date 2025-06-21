<?php

namespace Tests\Feature\Category;

use App\Enums\UserRoles;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CategoryControllerTest - Integration tests for Category CRUD operations.
 * 
 * This test class provides comprehensive integration tests for the CategoryController,
 * covering the complete CRUD lifecycle and ensuring proper authentication, authorization,
 * and data validation throughout all category management operations.
 * 
 * For specific operation tests, see:
 * - CategoryIndexTest: Category listing functionality
 * - CategoryStoreTest: Category creation functionality  
 * - CategoryShowTest: Category retrieval functionality
 * - CategoryUpdateTest: Category update functionality
 * - CategoryDestroyTest: Category deletion functionality
 */
class CategoryControllerTest extends TestCase
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
     * Test complete CRUD lifecycle for categories.
     * This integration test verifies that all CRUD operations work together correctly.
     */
    public function test_complete_category_crud_lifecycle(): void
    {
        // Given: An authenticated admin user
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user should exist in database');

        // Step 1: Create a new category
        $categoryData = [
            'name' => 'Integration Test Category',
            'description' => 'Category created for integration testing',
        ];

        $createResponse = $this->apiAs($admin, 'POST', route('category.store'), $categoryData);
        $createResponse->assertStatus(201);
        
        $createdCategory = $createResponse->json('data.category');
        $this->assertDatabaseHas('categories', $categoryData);

        // Step 2: Retrieve the created category
        $showResponse = $this->apiAs($admin, 'GET', route('category.show', $createdCategory['name']));
        $showResponse->assertStatus(200)
            ->assertJsonPath('data.category.name', 'Integration Test Category')
            ->assertJsonPath('data.category.description', 'Category created for integration testing');

        // Step 3: List all categories (should include our new category)
        $indexResponse = $this->apiAs($admin, 'GET', route('category.index'));
        $indexResponse->assertStatus(200);
        
        $categories = $indexResponse->json('data.categories');
        $foundCategory = collect($categories)->firstWhere('name', 'Integration Test Category');
        $this->assertNotNull($foundCategory, 'Created category should appear in category list');

        // Step 4: Update the category
        $updateData = [
            'name' => 'Updated Integration Category',
            'description' => 'Updated description for integration testing',
        ];

        $updateResponse = $this->apiAs($admin, 'PUT', route('category.update', $createdCategory['name']), $updateData);
        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.category.name', 'Updated Integration Category')
            ->assertJsonPath('data.category.description', 'Updated description for integration testing');

        $this->assertDatabaseHas('categories', $updateData);

        // Step 5: Delete the category
        $deleteResponse = $this->apiAs($admin, 'DELETE', route('category.destroy', 'Updated Integration Category'));
        $deleteResponse->assertStatus(200);

        $this->assertSoftDeleted('categories', [
            'name' => 'Updated Integration Category'
        ]);

        // Step 6: Verify category no longer appears in list
        $finalIndexResponse = $this->apiAs($admin, 'GET', route('category.index'));
        $finalIndexResponse->assertStatus(200);
        
        $finalCategories = $finalIndexResponse->json('data.categories');
        $deletedCategory = collect($finalCategories)->firstWhere('name', 'Updated Integration Category');
        $this->assertNull($deletedCategory, 'Deleted category should not appear in category list');
    }

    /**
     * Test category controller authentication requirements.
     * This ensures all endpoints require proper authentication.
     */
    public function test_category_controller_authentication_requirements(): void
    {
        // Given: No authentication and test data
        $categoryData = [
            'name' => 'Unauthorized Category',
            'description' => 'This should not work',
        ];

        // When: Attempting to access each endpoint without authentication
        $indexResponse = $this->getJson(route('category.index'));
        $storeResponse = $this->postJson(route('category.store'), $categoryData);
        
        // Create a category first for show/update/delete tests
        $admin = User::role(UserRoles::ADMIN)->first();
        $category = Category::factory()->create();

        $showResponse = $this->getJson(route('category.show', $category->name));
        $updateResponse = $this->putJson(route('category.update', $category->name), $categoryData);
        $deleteResponse = $this->deleteJson(route('category.destroy', $category->name));

        // Then: All should return 401 Unauthorized
        $indexResponse->assertStatus(401);
        $storeResponse->assertStatus(401);
        $showResponse->assertStatus(401);
        $updateResponse->assertStatus(401);
        $deleteResponse->assertStatus(401);
    }

    /**
     * Test category controller authorization requirements.
     * This ensures all endpoints require admin role.
     */
    public function test_category_controller_authorization_requirements(): void
    {
        // Given: A regular user (non-admin) and test data
        $user = User::factory()->create();
        $user->assignRole(UserRoles::USER);

        $categoryData = [
            'name' => 'Forbidden Category',
            'description' => 'This should be forbidden',
        ];

        // Create a category for show/update/delete tests
        $category = Category::factory()->create();

        // When: Regular user attempts to access each endpoint
        $indexResponse = $this->apiAs($user, 'GET', route('category.index'));
        $storeResponse = $this->apiAs($user, 'POST', route('category.store'), $categoryData);
        $showResponse = $this->apiAs($user, 'GET', route('category.show', $category->name));
        $updateResponse = $this->apiAs($user, 'PUT', route('category.update', $category->name), $categoryData);
        $deleteResponse = $this->apiAs($user, 'DELETE', route('category.destroy', $category->name));

        // Then: All should return 403 Forbidden
        $indexResponse->assertStatus(403);
        $storeResponse->assertStatus(403);
        $showResponse->assertStatus(403);
        $updateResponse->assertStatus(403);
        $deleteResponse->assertStatus(403);
    }

    /**
     * Test category controller API response structure consistency.
     * This ensures all endpoints return consistent ApiResponse format.
     */
    public function test_category_controller_api_response_structure_consistency(): void
    {
        // Given: An admin user and a test category
        $admin = User::role(UserRoles::ADMIN)->first();
        $category = Category::factory()->create();

        // When: Calling each endpoint
        $indexResponse = $this->apiAs($admin, 'GET', route('category.index'));
        $showResponse = $this->apiAs($admin, 'GET', route('category.show', $category->name));

        $storeData = [
            'name' => 'Consistency Test Category',
            'description' => 'Testing response structure',
        ];
        $storeResponse = $this->apiAs($admin, 'POST', route('category.store'), $storeData);

        $updateData = [
            'name' => 'Updated Consistency Category',
            'description' => 'Updated for testing response structure',
        ];
        $updateResponse = $this->apiAs($admin, 'PUT', route('category.update', $category->name), $updateData);

        $deleteResponse = $this->apiAs($admin, 'DELETE', route('category.destroy', 'Updated Consistency Category'));

        // Then: All responses should have consistent ApiResponse structure
        $commonStructure = ['status', 'message', 'data'];

        $indexResponse->assertStatus(200)->assertJsonStructure($commonStructure);
        $showResponse->assertStatus(200)->assertJsonStructure($commonStructure);
        $storeResponse->assertStatus(201)->assertJsonStructure($commonStructure);
        $updateResponse->assertStatus(200)->assertJsonStructure($commonStructure);
        $deleteResponse->assertStatus(200)->assertJsonStructure($commonStructure);
    }

    /**
     * Test category controller HTTP method validation.
     * This ensures proper HTTP method restrictions for each endpoint.
     */
    public function test_category_controller_http_method_validation(): void
    {
        // Given: An admin user and a test category
        $admin = User::role(UserRoles::ADMIN)->first();
        $category = Category::factory()->create();

        $testData = [
            'name' => 'Method Test Category',
            'description' => 'Testing HTTP methods',
        ];

        // When/Then: Testing incorrect HTTP methods return 405 Method Not Allowed
        
        // Index should only accept GET
        $this->apiAs($admin, 'PUT', route('category.index'))->assertStatus(405);
        
        // Store should only accept POST
        $this->apiAs($admin, 'DELETE', route('category.store'))->assertStatus(405);
        
        // Show should only accept GET
        $this->apiAs($admin, 'POST', route('category.show', $category->name))->assertStatus(405);
        
        // Update should only accept PUT
        $this->apiAs($admin, 'POST', route('category.update', $category->name), $testData)->assertStatus(405);
        
        // Destroy should only accept DELETE
        $this->apiAs($admin, 'POST', route('category.destroy', $category->name))->assertStatus(405);
    }

    /**
     * Test category controller error handling consistency.
     * This ensures proper error responses for various scenarios.
     */
    public function test_category_controller_error_handling_consistency(): void
    {
        // Given: An admin user
        $admin = User::role(UserRoles::ADMIN)->first();

        // Test 1: 404 for non-existent category
        $showResponse = $this->apiAs($admin, 'GET', route('category.show', 'non-existent-category'));
        $updateResponse = $this->apiAs($admin, 'PUT', route('category.update', 'non-existent-category'), [
            'name' => 'Test', 'description' => 'Test'
        ]);
        $deleteResponse = $this->apiAs($admin, 'DELETE', route('category.destroy', 'non-existent-category'));

        $showResponse->assertStatus(404);
        $updateResponse->assertStatus(404);
        $deleteResponse->assertStatus(404);

        // Test 2: 422 for validation errors
        $invalidStoreResponse = $this->apiAs($admin, 'POST', route('category.store'), [
            'name' => '', // Required field missing
            'description' => 'Test description'
        ]);

        $category = Category::factory()->create();
        $invalidUpdateResponse = $this->apiAs($admin, 'PUT', route('category.update', $category->name), [
            'name' => '', // Required field missing
            'description' => 'Test description'
        ]);

        $invalidStoreResponse->assertStatus(422);
        $invalidUpdateResponse->assertStatus(422);
    }
}