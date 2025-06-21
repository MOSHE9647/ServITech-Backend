<?php

namespace Tests\Feature\RepairRequest;

use App\Enums\RepairStatus;
use App\Enums\UserRoles;
use App\Models\RepairRequest;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepairRequestUpdateTest extends TestCase
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
            UserSeeder::class,
        ]);
    }

    /**
     * Test that an authenticated admin user can update a repair request.
     * This ensures that only admin users can update repair requests successfully.
     */
    public function test_an_authenticated_admin_user_can_update_a_repair_request(): void
    {
        // Given: An existing repair request
        $repairRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::PENDING->value,
            'repair_details' => 'Initial details',
            'repair_price' => null,
            'repaired_at' => null,
        ]);

        // And: Valid update data
        $updateData = [
            'repair_status' => RepairStatus::IN_PROGRESS->value,
            'repair_details' => 'Updated repair details - work in progress',
            'repair_price' => 150.50,
            'article_serialnumber' => 'SN123456789',
            'article_accesories' => 'Updated accessories list',
        ];

        // When: An admin user attempts to update the repair request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'PUT', route('repair-request.update', ['repairRequest' => $repairRequest->receipt_number]), $updateData);

        // Then: The request should succeed and the repair request should be updated
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'repairRequest' => [
                    'id',
                    'receipt_number',
                    'customer_name',
                    'customer_phone',
                    'customer_email',
                    'article_name',
                    'article_type',
                    'article_brand',
                    'article_model',
                    'article_serialnumber',
                    'article_accesories',
                    'article_problem',
                    'repair_status',
                    'repair_details',
                    'repair_price',
                    'received_at',
                    'repaired_at',
                    'images'
                ]
            ]
        ]);

        $response->assertJsonFragment([
            'message' => __('messages.common.updated', ['item' => __('messages.entities.repair_request.singular')])
        ]);

        // Verify the repair request was updated in the database
        $this->assertDatabaseHas('repair_requests', [
            'id' => $repairRequest->id,
            'repair_status' => RepairStatus::IN_PROGRESS->value,
            'repair_details' => 'Updated repair details - work in progress',
            'repair_price' => 150.50,
            'article_serialnumber' => 'SN123456789',
            'article_accesories' => 'Updated accessories list',
        ]);

        // Verify the response contains updated data
        $responseData = $response->json();
        $repairRequestData = $responseData['data']['repairRequest'];
        $this->assertEquals(RepairStatus::IN_PROGRESS->value, $repairRequestData['repair_status']);
        $this->assertEquals('Updated repair details - work in progress', $repairRequestData['repair_details']);
        $this->assertEquals(150.50, $repairRequestData['repair_price']);
    }

    /**
     * Test that an authenticated non-admin user cannot update a repair request.
     * This ensures that only admin users can update repair requests.
     */
    public function test_an_authenticated_non_admin_user_cannot_update_a_repair_request(): void
    {
        // Given: An existing repair request
        $repairRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::PENDING->value,
        ]);

        // And: Valid update data
        $updateData = [
            'repair_status' => RepairStatus::COMPLETED->value,
            'repair_details' => 'Repair completed',
        ];

        // When: A non-admin user attempts to update the repair request
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $response = $this->apiAs($user, 'PUT', route('repair-request.update', ['repairRequest' => $repairRequest->receipt_number]), $updateData);

        // Then: The request should fail with a 403 Forbidden status
        $response->assertStatus(403);
        $response->assertJsonStructure(['status', 'message']);

        // Verify that the repair request was not updated
        $this->assertDatabaseHas('repair_requests', [
            'id' => $repairRequest->id,
            'repair_status' => RepairStatus::PENDING->value, // Should remain unchanged
        ]);
    }

    /**
     * Test that a non-authenticated user cannot update a repair request.
     * This ensures that only authenticated users can update repair requests.
     */
    public function test_a_non_authenticated_user_cannot_update_a_repair_request(): void
    {
        // Given: An existing repair request
        $repairRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::PENDING->value,
        ]);

        // And: Valid update data
        $updateData = [
            'repair_status' => RepairStatus::COMPLETED->value,
        ];

        // When: A non-authenticated user attempts to update the repair request
        $response = $this->putJson(route('repair-request.update', ['repairRequest' => $repairRequest->receipt_number]), $updateData);

        // Then: The request should fail with a 401 Unauthorized status
        $response->assertStatus(401);
        $response->assertJsonStructure(['status', 'message']);

        // Verify that the repair request was not updated
        $this->assertDatabaseHas('repair_requests', [
            'id' => $repairRequest->id,
            'repair_status' => RepairStatus::PENDING->value, // Should remain unchanged
        ]);
    }

    /**
     * Test that updating a non-existent repair request returns 404.
     * This ensures proper error handling for invalid receipt numbers.
     */
    public function test_updating_a_non_existent_repair_request_returns_404(): void
    {
        // Given: A non-existent receipt number
        $nonExistentReceiptNumber = 'REC-999999';

        // And: Valid update data
        $updateData = [
            'repair_status' => RepairStatus::COMPLETED->value,
        ];

        // When: An admin user attempts to update the non-existent repair request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'PUT', route('repair-request.update', ['repairRequest' => $nonExistentReceiptNumber]), $updateData);

        // Then: The request should fail with a 404 Not Found status
        $response->assertStatus(404);
    }

    /**
     * Test that repair_status field is required for update.
     * This ensures that the repair_status field is mandatory.
     */
    public function test_repair_status_must_be_required_for_update(): void
    {
        // Given: An existing repair request
        $repairRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::PENDING->value,
        ]);

        // And: Update data without repair_status
        $updateData = [
            'repair_details' => 'Some details',
        ];

        // When: An admin user attempts to update without repair_status
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'PUT', route('repair-request.update', ['repairRequest' => $repairRequest->receipt_number]), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['repair_status']]);

        // Check that the error message exists
        $responseData = $response->json();
        $this->assertArrayHasKey('repair_status', $responseData['errors']);
    }

    /**
     * Test that repair_status must be a valid enum value.
     * This ensures that invalid repair status values return validation errors.
     */
    public function test_repair_status_must_be_valid_enum_value_for_update(): void
    {
        // Given: An existing repair request
        $repairRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::PENDING->value,
        ]);

        // And: Update data with invalid repair_status
        $updateData = [
            'repair_status' => 'invalid_status',
        ];

        // When: An admin user attempts to update with invalid repair_status
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'PUT', route('repair-request.update', ['repairRequest' => $repairRequest->receipt_number]), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['repair_status']]);
    }

    /**
     * Test that repair_price must be numeric if provided.
     * This ensures that non-numeric values for repair_price return validation errors.
     */
    public function test_repair_price_must_be_numeric_for_update(): void
    {
        // Given: An existing repair request
        $repairRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::PENDING->value,
        ]);

        // And: Update data with non-numeric repair_price
        $updateData = [
            'repair_status' => RepairStatus::COMPLETED->value,
            'repair_price' => 'not-a-number',
        ];

        // When: An admin user attempts to update with invalid repair_price
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'PUT', route('repair-request.update', ['repairRequest' => $repairRequest->receipt_number]), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['repair_price']]);
    }

    /**
     * Test that article_serialnumber must have at least 6 characters if provided.
     * This ensures that short serial numbers return validation errors.
     */
    public function test_article_serialnumber_must_have_minimum_length_for_update(): void
    {
        // Given: An existing repair request
        $repairRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::PENDING->value,
        ]);

        // And: Update data with short article_serialnumber
        $updateData = [
            'repair_status' => RepairStatus::COMPLETED->value,
            'article_serialnumber' => 'SN123', // Less than 6 characters
        ];

        // When: An admin user attempts to update with invalid article_serialnumber
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'PUT', route('repair-request.update', ['repairRequest' => $repairRequest->receipt_number]), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['article_serialnumber']]);
    }

    /**
     * Test that repair request can be updated to different statuses.
     * This ensures that all valid repair statuses can be used in updates.
     */
    public function test_can_update_repair_request_to_different_statuses(): void
    {
        // Test updating to each valid status
        $statuses = [
            RepairStatus::PENDING,
            RepairStatus::IN_PROGRESS,
            RepairStatus::WAITING_PARTS,
            RepairStatus::COMPLETED,
            RepairStatus::DELIVERED,
            RepairStatus::CANCELED,
        ];

        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        foreach ($statuses as $status) {
            // Given: A repair request with initial status
            $repairRequest = RepairRequest::factory()->create([
                'repair_status' => RepairStatus::PENDING->value,
            ]);

            // When: Admin updates to the current status
            $updateData = [
                'repair_status' => $status->value,
                'repair_details' => "Updated to {$status->value} status",
            ];

            $response = $this->apiAs($admin, 'PUT', route('repair-request.update', ['repairRequest' => $repairRequest->receipt_number]), $updateData);

            // Then: The update should succeed
            $response->assertStatus(200);
            $this->assertDatabaseHas('repair_requests', [
                'id' => $repairRequest->id,
                'repair_status' => $status->value,
            ]);
        }
    }

    /**
     * Test that optional fields can be updated independently.
     * This ensures that each optional field can be updated without affecting others.
     */
    public function test_optional_fields_can_be_updated_independently(): void
    {
        // Given: An existing repair request
        $repairRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::PENDING->value,
            'repair_details' => null,
            'repair_price' => null,
            'article_serialnumber' => null,
            'article_accesories' => null,
        ]);

        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        // Test updating repair_details only
        $response = $this->apiAs($admin, 'PUT', route('repair-request.update', ['repairRequest' => $repairRequest->receipt_number]), [
            'repair_status' => RepairStatus::IN_PROGRESS->value,
            'repair_details' => 'Only details updated',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('repair_requests', [
            'id' => $repairRequest->id,
            'repair_details' => 'Only details updated',
            'repair_price' => null, // Should remain null
        ]);

        // Test updating repair_price only
        $response = $this->apiAs($admin, 'PUT', route('repair-request.update', ['repairRequest' => $repairRequest->receipt_number]), [
            'repair_status' => RepairStatus::COMPLETED->value,
            'repair_price' => 99.99,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('repair_requests', [
            'id' => $repairRequest->id,
            'repair_price' => 99.99,
        ]);
    }

    /**
     * Test that repair request update includes images in response.
     * This ensures that associated images are properly loaded in the response.
     */
    public function test_updated_repair_request_includes_images_in_response(): void
    {
        // Given: A repair request with associated images
        $repairRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::PENDING->value,
        ]);

        // Create some test images
        $repairRequest->images()->createMany([
            [
                'path' => 'repair_requests/test1.jpg',
                'title' => 'repair_request_image_' . $repairRequest->receipt_number . '_1',
                'alt' => 'Test image 1'
            ],
            [
                'path' => 'repair_requests/test2.jpg',
                'title' => 'repair_request_image_' . $repairRequest->receipt_number . '_2',
                'alt' => 'Test image 2'
            ]
        ]);

        // When: Admin updates the repair request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $updateData = [
            'repair_status' => RepairStatus::COMPLETED->value,
            'repair_details' => 'Work completed successfully',
        ];

        $response = $this->apiAs($admin, 'PUT', route('repair-request.update', ['repairRequest' => $repairRequest->receipt_number]), $updateData);

        // Then: The response should include the images
        $response->assertStatus(200);
        $responseData = $response->json();
        $repairRequestData = $responseData['data']['repairRequest'];

        $this->assertArrayHasKey('images', $repairRequestData);
        $this->assertCount(2, $repairRequestData['images']);
    }

    /**
     * Test that successful update returns the correct message and structure.
     * This ensures that the API returns the expected response format.
     */
    public function test_successful_update_returns_correct_message_and_structure(): void
    {
        // Given: An existing repair request
        $repairRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::PENDING->value,
        ]);

        // When: Admin updates the repair request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $updateData = [
            'repair_status' => RepairStatus::COMPLETED->value,
            'repair_details' => 'Update test completed',
        ];

        $response = $this->apiAs($admin, 'PUT', route('repair-request.update', ['repairRequest' => $repairRequest->receipt_number]), $updateData);

        // Then: The response should have the correct structure and message
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'repairRequest' => [
                    'id',
                    'receipt_number',
                    'customer_name',
                    'repair_status',
                    'repair_details',
                    'images'
                ]
            ]
        ]);

        $response->assertJsonFragment([
            'status' => 200,
            'message' => __('messages.common.updated', ['item' => __('messages.entities.repair_request.singular')])
        ]);
    }
}