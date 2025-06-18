<?php

namespace Tests\Feature\Category;

use App\Enums\UserRoles;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryDestroyTest extends TestCase
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
     * Test that an authenticated admin can delete a category.
     * This ensures that only authenticated admin users can delete categories successfully.
     */
    public function test_authenticated_admin_can_delete_category(): void
    {
        // Given: An authenticated admin user and an existing category
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user should exist in database');

        $category = Category::factory()->create([
            'name' => 'Category to Delete',
            'description' => 'This category will be deleted'
        ]);

        // When: The admin attempts to delete the category
        $response = $this->apiAs($admin, 'DELETE', route('category.destroy', $category->name));

        // Then: The request should succeed
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data'
            ])
            ->assertJsonFragment([
                'status' => 200,
                'message' => __('messages.common.deleted', [
                    'item' => __('messages.entities.category.singular')
                ])
            ]);

        // And: The category should be soft deleted from the database
        $this->assertSoftDeleted('categories', [
            'id' => $category->id,
            'name' => 'Category to Delete'
        ]);

        // And: The category should not be found in regular queries
        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
            'deleted_at' => null
        ]);
    }

    /**
     * Test that deleting non-existent category returns 404.
     * This ensures that proper error handling for missing categories during deletion.
     */
    public function test_deleting_non_existent_category_returns_404(): void
    {
        // Given: An authenticated admin user and a non-existent category name
        $admin = User::role(UserRoles::ADMIN)->first();
        $nonExistentCategoryName = 'non-existent-category-for-deletion';

        // Ensure the category doesn't exist
        $this->assertDatabaseMissing('categories', ['name' => $nonExistentCategoryName]);

        // When: The admin attempts to delete non-existent category
        $response = $this->apiAs($admin, 'DELETE', route('category.destroy', $nonExistentCategoryName));

        // Then: The request should return 404 error
        $response->assertStatus(404);
    }

    /**
     * Test that unauthenticated users cannot delete categories.
     * This ensures that authentication is required for category deletion.
     */
    public function test_unauthenticated_user_cannot_delete_category(): void
    {
        // Given: An existing category
        $category = Category::factory()->create([
            'name' => 'Protected Category',
            'description' => 'This should not be deleted by unauthenticated user'
        ]);

        // When: An unauthenticated user attempts to delete category
        $response = $this->deleteJson(route('category.destroy', $category->name));

        // Then: The response should return a 401 status (Unauthorized)
        $response->assertStatus(401);

        // And: The category should still exist in the database
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Protected Category',
            'deleted_at' => null
        ]);
    }

    /**
     * Test that non-admin users cannot delete categories.
     * This ensures that only admin users can delete categories.
     */
    public function test_non_admin_user_cannot_delete_category(): void
    {
        // Given: A regular user (non-admin) and an existing category
        $user = User::factory()->create();
        $user->assignRole(UserRoles::USER);

        $category = Category::factory()->create([
            'name' => 'Admin Only Category',
            'description' => 'This should not be deleted by regular user'
        ]);

        // When: The regular user attempts to delete category
        $response = $this->apiAs($user, 'DELETE', route('category.destroy', $category->name));

        // Then: The response should return a 403 status (Forbidden)
        $response->assertStatus(403);

        // And: The category should still exist in the database
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Admin Only Category',
            'deleted_at' => null
        ]);
    }

    /**
     * Test that category destroy only accepts DELETE method.
     * This ensures that only DELETE requests are allowed for category deletion.
     */
    public function test_category_destroy_only_accepts_delete_method(): void
    {
        // Given: An admin user and an existing category
        $admin = User::role(UserRoles::ADMIN)->first();
        $category = Category::factory()->create();

        // When: Attempting to use GET method on destroy endpoint
        $response = $this->apiAs($admin, 'POST', route('category.destroy', $category->name));

        // Then: The response should return a 405 status (Method Not Allowed)
        $response->assertStatus(405);
    }

    /**
     * Test that already soft deleted category returns 404.
     * This ensures that attempting to delete already deleted categories returns proper error.
     */
    public function test_already_soft_deleted_category_returns_404(): void
    {
        // Given: An admin user and a soft deleted category
        $admin = User::role(UserRoles::ADMIN)->first();
        $category = Category::factory()->create([
            'name' => 'already-deleted-category'
        ]);
        
        // Soft delete the category first
        $category->delete();

        // When: The admin attempts to delete the already deleted category
        $response = $this->apiAs($admin, 'DELETE', route('category.destroy', 'already-deleted-category'));

        // Then: The request should return 404 (since exists() returns false for soft deleted)
        $response->assertStatus(404);
    }

    /**
     * Test that category deletion preserves database integrity.
     * This ensures that related data integrity is maintained after deletion.
     */
    public function test_category_deletion_preserves_database_integrity(): void
    {
        // Given: An admin user and multiple categories
        $admin = User::role(UserRoles::ADMIN)->first();
        
        $category1 = Category::factory()->create(['name' => 'category-to-keep']);
        $category2 = Category::factory()->create(['name' => 'category-to-delete']);
        $category3 = Category::factory()->create(['name' => 'another-category-to-keep']);

        $originalCount = Category::count();

        // When: One category is deleted
        $response = $this->apiAs($admin, 'DELETE', route('category.destroy', 'category-to-delete'));

        // Then: The deletion should succeed
        $response->assertStatus(200);

        // And: The total count should decrease by 1 (soft delete reduces visible count)
        $this->assertEquals($originalCount - 1, Category::count());

        // And: Other categories should remain unchanged
        $this->assertDatabaseHas('categories', [
            'id' => $category1->id,
            'name' => 'category-to-keep',
            'deleted_at' => null
        ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category3->id,
            'name' => 'another-category-to-keep',
            'deleted_at' => null
        ]);

        // And: Deleted category should be soft deleted
        $this->assertSoftDeleted('categories', [
            'id' => $category2->id,
            'name' => 'category-to-delete'
        ]);
    }

    /**
     * Test that multiple categories can be deleted successfully.
     * This ensures that the deletion process works for multiple categories.
     */
    public function test_multiple_categories_can_be_deleted(): void
    {
        // Given: An admin user and multiple categories
        $admin = User::role(UserRoles::ADMIN)->first();
        
        $categories = [
            Category::factory()->create(['name' => 'category-one']),
            Category::factory()->create(['name' => 'category-two']),
            Category::factory()->create(['name' => 'category-three'])
        ];

        // When: Deleting multiple categories
        foreach ($categories as $category) {
            $response = $this->apiAs($admin, 'DELETE', route('category.destroy', $category->name));
            $response->assertStatus(200);
        }

        // Then: All categories should be soft deleted
        foreach ($categories as $category) {
            $this->assertSoftDeleted('categories', [
                'id' => $category->id,
                'name' => $category->name
            ]);
        }
    }

    /**
     * Test that category deletion response doesn't contain sensitive data.
     * This ensures that the deletion response only includes necessary information.
     */
    public function test_category_deletion_response_doesnt_contain_sensitive_data(): void
    {
        // Given: An admin user and a category
        $admin = User::role(UserRoles::ADMIN)->first();
        $category = Category::factory()->create();

        // When: The admin deletes the category
        $response = $this->apiAs($admin, 'DELETE', route('category.destroy', $category->name));

        // Then: The response should be successful
        $response->assertStatus(200);

        $responseData = $response->json();

        // And: The response should not contain the deleted category data
        $this->assertArrayNotHasKey('category', $responseData['data'] ?? []);
        
        // And: Response should only contain status and message
        $this->assertArrayHasKey('status', $responseData);
        $this->assertArrayHasKey('message', $responseData);
    }

    /**
     * Test that category name route binding works correctly for deletion.
     * This ensures that route model binding by name functions properly for deletion.
     */
    public function test_category_name_route_binding_works_correctly_for_deletion(): void
    {
        // Given: An admin user and categories with different names
        $admin = User::role(UserRoles::ADMIN)->first();
        
        $category1 = Category::factory()->create(['name' => 'technology-category']);
        $category2 = Category::factory()->create(['name' => 'sports-category']);

        // When: Deleting specific category by name
        $response = $this->apiAs($admin, 'DELETE', route('category.destroy', 'technology-category'));

        // Then: The deletion should succeed
        $response->assertStatus(200);

        // And: Only the specified category should be deleted
        $this->assertSoftDeleted('categories', [
            'id' => $category1->id,
            'name' => 'technology-category'
        ]);

        // And: The other category should remain
        $this->assertDatabaseHas('categories', [
            'id' => $category2->id,
            'name' => 'sports-category',
            'deleted_at' => null
        ]);
    }

    /**
     * Test that category deletion handles special characters in names.
     * This ensures that categories with special characters can be deleted properly.
     */
    public function test_category_deletion_handles_special_characters_in_names(): void
    {
        // Given: An admin user and a category with special characters
        $admin = User::role(UserRoles::ADMIN)->first();
        $category = Category::factory()->create([
            'name' => 'Technology & Innovation',
            'description' => 'Articles about tech & innovation'
        ]);

        // When: The admin deletes the category with special characters
        $response = $this->apiAs($admin, 'DELETE', route('category.destroy', 'Technology & Innovation'));

        // Then: The deletion should succeed
        $response->assertStatus(200);

        // And: The category should be soft deleted
        $this->assertSoftDeleted('categories', [
            'id' => $category->id,
            'name' => 'Technology & Innovation'
        ]);
    }
}
