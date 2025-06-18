<?php

namespace Tests\Feature\Category;

use App\Enums\UserRoles;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryUpdateTest extends TestCase
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
     * Test that an authenticated admin can update a category.
     * This ensures that only authenticated admin users can update categories successfully.
     */
    public function test_authenticated_admin_can_update_category(): void
    {
        // Given: An authenticated admin user and an existing category
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user should exist in database');

        $category = Category::factory()->create([
            'name' => 'Original Category',
            'description' => 'Original description'
        ]);

        $updateData = [
            'name' => 'Updated Technology Category',
            'description' => 'Updated description for technology articles.',
        ];

        // When: The admin attempts to update the category
        $response = $this->apiAs($admin, 'PUT', route('category.update', $category->name), $updateData);

        // Then: The request should succeed and category should be updated
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
                    ]
                ]
            ])
            ->assertJsonFragment([
                'status' => 200,
                'message' => __('messages.common.updated', [
                    'item' => __('messages.entities.category.singular')
                ])
            ])
            ->assertJsonPath('data.category.id', $category->id)
            ->assertJsonPath('data.category.name', 'Updated Technology Category')
            ->assertJsonPath('data.category.description', 'Updated description for technology articles.');

        // And: The category should be updated in the database
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Technology Category',
            'description' => 'Updated description for technology articles.'
        ]);

        // And: The old data should no longer exist
        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
            'name' => 'Original Category',
            'description' => 'Original description'
        ]);
    }

    /**
     * Test that category can be updated without description.
     * This ensures that description field is optional during updates.
     */
    public function test_category_can_be_updated_to_null_description(): void
    {
        // Given: An admin user and existing category with description
        $admin = User::role(UserRoles::ADMIN)->first();
        $category = Category::factory()->create([
            'name' => 'Category With Description',
            'description' => 'This has a description'
        ]);

        $updateData = [
            'name' => 'Updated Category Name',
            'description' => null,
        ];

        // When: The admin updates the category removing description
        $response = $this->apiAs($admin, 'PUT', route('category.update', $category->name), $updateData);

        // Then: The request should succeed
        $response->assertStatus(200)
            ->assertJsonPath('data.category.name', 'Updated Category Name')
            ->assertJsonPath('data.category.description', null);

        // And: The category should be updated in database
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Category Name',
            'description' => null
        ]);
    }

    /**
     * Test that name field is required for category update.
     * This ensures that missing name field returns validation errors.
     */
    public function test_name_is_required_for_category_update(): void
    {
        // Given: An admin user and existing category
        $admin = User::role(UserRoles::ADMIN)->first();
        $category = Category::factory()->create();

        $updateData = [
            'description' => 'Updated description without name',
        ];

        // When: The admin attempts to update category without name
        $response = $this->apiAs($admin, 'PUT', route('category.update', $category->name), $updateData);

        // Then: The request should fail with validation error
        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => ['name']
            ])
            ->assertJsonPath('errors.name', __('validation.required', [
                'attribute' => __('validation.attributes.name')
            ]));

        // And: The category should not be updated
        $category->refresh();
        $this->assertNotEquals('Updated description without name', $category->description);
    }

    /**
     * Test that name must be a string during update.
     * This ensures that non-string values for name field return validation errors.
     */
    public function test_name_must_be_string_during_update(): void
    {
        // Given: An admin user and existing category
        $admin = User::role(UserRoles::ADMIN)->first();
        $category = Category::factory()->create();

        $updateData = [
            'name' => 123456,
            'description' => 'Valid description',
        ];

        // When: The admin attempts to update category with invalid name
        $response = $this->apiAs($admin, 'PUT', route('category.update', $category->name), $updateData);

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
     * Test that name cannot exceed maximum length during update.
     * This ensures that excessively long names are rejected.
     */
    public function test_name_cannot_exceed_maximum_length_during_update(): void
    {
        // Given: An admin user and existing category
        $admin = User::role(UserRoles::ADMIN)->first();
        $category = Category::factory()->create();

        $updateData = [
            'name' => str_repeat('a', 256), // 256 characters
            'description' => 'Valid description',
        ];

        // When: The admin attempts to update category with long name
        $response = $this->apiAs($admin, 'PUT', route('category.update', $category->name), $updateData);

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
     * Test that description must be a string when provided during update.
     * This ensures that non-string values for description field return validation errors.
     */
    public function test_description_must_be_string_when_provided_during_update(): void
    {
        // Given: An admin user and existing category
        $admin = User::role(UserRoles::ADMIN)->first();
        $category = Category::factory()->create();

        $updateData = [
            'name' => 'Valid Updated Name',
            'description' => 123456,
        ];

        // When: The admin attempts to update category with invalid description
        $response = $this->apiAs($admin, 'PUT', route('category.update', $category->name), $updateData);

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
     * Test that description cannot exceed maximum length during update.
     * This ensures that excessively long descriptions are rejected.
     */
    public function test_description_cannot_exceed_maximum_length_during_update(): void
    {
        // Given: An admin user and existing category
        $admin = User::role(UserRoles::ADMIN)->first();
        $category = Category::factory()->create();

        $updateData = [
            'name' => 'Valid Updated Name',
            'description' => str_repeat('b', 256), // 256 characters
        ];

        // When: The admin attempts to update category with long description
        $response = $this->apiAs($admin, 'PUT', route('category.update', $category->name), $updateData);

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
     * Test that updating non-existent category returns 404.
     * This ensures that proper error handling for missing categories during update.
     */
    public function test_updating_non_existent_category_returns_404(): void
    {
        // Given: An authenticated admin user and update data
        $admin = User::role(UserRoles::ADMIN)->first();
        $nonExistentCategoryName = 'non-existent-category';

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ];

        // When: The admin attempts to update non-existent category
        $response = $this->apiAs($admin, 'PUT', route('category.update', $nonExistentCategoryName), $updateData);

        // Then: The request should return 404 error
        $response->assertStatus(404);
    }

    /**
     * Test that unauthenticated users cannot update categories.
     * This ensures that authentication is required for category updates.
     */
    public function test_unauthenticated_user_cannot_update_category(): void
    {
        // Given: An existing category and update data
        $category = Category::factory()->create();
        $updateData = [
            'name' => 'Unauthorized Update',
            'description' => 'This should not be updated',
        ];

        // When: An unauthenticated user attempts to update category
        $response = $this->putJson(route('category.update', $category->name), $updateData);

        // Then: The response should return a 401 status (Unauthorized)
        $response->assertStatus(401);

        // And: The category should not be updated
        $category->refresh();
        $this->assertNotEquals('Unauthorized Update', $category->name);
    }

    /**
     * Test that non-admin users cannot update categories.
     * This ensures that only admin users can update categories.
     */
    public function test_non_admin_user_cannot_update_category(): void
    {
        // Given: A regular user (non-admin), existing category and update data
        $user = User::factory()->create();
        $user->assignRole(UserRoles::USER);

        $category = Category::factory()->create();
        $updateData = [
            'name' => 'Forbidden Update',
            'description' => 'This should not be updated by regular user',
        ];

        // When: The regular user attempts to update category
        $response = $this->apiAs($user, 'PUT', route('category.update', $category->name), $updateData);

        // Then: The response should return a 403 status (Forbidden)
        $response->assertStatus(403);

        // And: The category should not be updated
        $category->refresh();
        $this->assertNotEquals('Forbidden Update', $category->name);
    }

    /**
     * Test that category update only accepts PUT method.
     * This ensures that only PUT requests are allowed for category updates.
     */
    public function test_category_update_only_accepts_put_method(): void
    {
        // Given: An admin user, existing category and update data
        $admin = User::role(UserRoles::ADMIN)->first();
        $category = Category::factory()->create();
        $updateData = [
            'name' => 'Test Update',
            'description' => 'Test Description',
        ];

        // When: Attempting to use POST method on update endpoint
        $response = $this->apiAs($admin, 'POST', route('category.update', $category->name), $updateData);

        // Then: The response should return a 405 status (Method Not Allowed)
        $response->assertStatus(405);
    }

    /**
     * Test that multiple category updates work correctly.
     * This ensures that categories can be updated multiple times.
     */
    public function test_multiple_category_updates_work_correctly(): void
    {
        // Given: An admin user and a category
        $admin = User::role(UserRoles::ADMIN)->first();
        $category = Category::factory()->create([
            'name' => 'Original Category',
            'description' => 'Original description'
        ]);

        // When: The category is updated multiple times
        $firstUpdate = [
            'name' => 'First Update',
            'description' => 'First update description',
        ];

        $response1 = $this->apiAs($admin, 'PUT', route('category.update', $category->name), $firstUpdate);
        $response1->assertStatus(200);

        $secondUpdate = [
            'name' => 'Second Update',
            'description' => 'Second update description',
        ];

        $response2 = $this->apiAs($admin, 'PUT', route('category.update', 'First Update'), $secondUpdate);

        // Then: Both updates should be successful
        $response2->assertStatus(200)
            ->assertJsonPath('data.category.name', 'Second Update')
            ->assertJsonPath('data.category.description', 'Second update description');

        // And: The final data should be the second update
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Second Update',
            'description' => 'Second update description'
        ]);
    }

    /**
     * Test that updated_at timestamp is modified during update.
     * This ensures that the updated_at field is properly maintained.
     */
    public function test_updated_at_timestamp_is_modified_during_update(): void
    {
        // Given: An admin user and an existing category
        $admin = User::role(UserRoles::ADMIN)->first();
        $category = Category::factory()->create();
        $originalUpdatedAt = $category->updated_at;

        // Wait a moment to ensure different timestamps
        sleep(1);

        $updateData = [
            'name' => 'Updated Category Name',
            'description' => 'Updated description',
        ];

        // When: The admin updates the category
        $response = $this->apiAs($admin, 'PUT', route('category.update', $category->name), $updateData);

        // Then: The update should succeed
        $response->assertStatus(200);

        // And: The updated_at timestamp should be different
        $category->refresh();
        $this->assertNotEquals($originalUpdatedAt, $category->updated_at);
        $this->assertTrue($category->updated_at > $originalUpdatedAt);
    }
}
