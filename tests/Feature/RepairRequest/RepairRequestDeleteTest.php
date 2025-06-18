<?php

namespace Tests\Feature\RepairRequest;

use App\Enums\RepairStatus;
use App\Enums\UserRoles;
use App\Models\RepairRequest;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RepairRequestDeleteTest extends TestCase
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
     * Test that an authenticated admin user can delete a repair request.
     * This ensures that only admin users can delete repair requests successfully.
     */
    public function test_an_authenticated_admin_user_can_delete_a_repair_request(): void
    {
        Storage::fake('public');
        
        // Given: An existing repair request
        $repairRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::PENDING->value,
        ]);
        $this->assertNotNull($repairRequest, 'Repair request not found');

        // When: An admin user attempts to delete the repair request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'DELETE', route('repair-request.destroy', ['repairRequest' => $repairRequest->receipt_number]));

        // Then: The request should succeed, and the repair request should be deleted from the database
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message']);
        
        $response->assertJsonFragment([
            'message' => __('messages.common.deleted', ['item' => __('messages.entities.repair_request.singular')])
        ]);

        // Verify that the repair request is soft deleted
        $this->assertSoftDeleted('repair_requests', ['id' => $repairRequest->id]);
    }

    /**
     * Test that an authenticated non-admin user cannot delete a repair request.
     * This ensures that only admin users can delete repair requests.
     */
    public function test_an_authenticated_non_admin_user_cannot_delete_a_repair_request(): void
    {
        // Given: An existing repair request
        $repairRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::PENDING->value,
        ]);
        $this->assertNotNull($repairRequest, 'Repair request not found');

        // When: A non-admin user attempts to delete the repair request
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $response = $this->apiAs($user, 'DELETE', route('repair-request.destroy', ['repairRequest' => $repairRequest->receipt_number]));

        // Then: The request should fail with a 403 Forbidden status
        $response->assertStatus(403);
        $response->assertJsonStructure(['status', 'message']);
        
        // Verify that the repair request was not deleted
        $this->assertDatabaseHas('repair_requests', ['id' => $repairRequest->id, 'deleted_at' => null]);
    }

    /**
     * Test that a non-authenticated user cannot delete a repair request.
     * This ensures that only authenticated users can delete repair requests.
     */
    public function test_a_non_authenticated_user_cannot_delete_a_repair_request(): void
    {
        // Given: An existing repair request
        $repairRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::PENDING->value,
        ]);
        $this->assertNotNull($repairRequest, 'Repair request not found');

        // When: A non-authenticated user attempts to delete the repair request
        $response = $this->deleteJson(route('repair-request.destroy', ['repairRequest' => $repairRequest->receipt_number]));

        // Then: The request should fail with a 401 Unauthorized status
        $response->assertStatus(401);
        $response->assertJsonStructure(['status', 'message']);
        
        // Verify that the repair request was not deleted
        $this->assertDatabaseHas('repair_requests', ['id' => $repairRequest->id, 'deleted_at' => null]);
    }

    /**
     * Test that deleting a non-existent repair request returns 404.
     * This ensures proper error handling for invalid receipt numbers.
     */
    public function test_deleting_a_non_existent_repair_request_returns_404(): void
    {
        // Given: A non-existent receipt number
        $nonExistentReceiptNumber = 'REC-999999';

        // When: An admin user attempts to delete the non-existent repair request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'DELETE', route('repair-request.destroy', ['repairRequest' => $nonExistentReceiptNumber]));

        // Then: The request should fail with a 404 Not Found status
        $response->assertStatus(404);
    }

    /**
     * Test that deleting a repair request with images removes the images.
     * This ensures that associated images are properly deleted when a repair request is deleted.
     */
    public function test_deleting_repair_request_with_images_removes_images(): void
    {
        Storage::fake('public');
        
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

        // Create the actual files in storage
        Storage::put('repair_requests/test1.jpg', 'fake image content');
        Storage::put('repair_requests/test2.jpg', 'fake image content');

        $this->assertEquals(2, $repairRequest->images()->count(), 'Repair request should have 2 images');

        // When: An admin user deletes the repair request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'DELETE', route('repair-request.destroy', ['repairRequest' => $repairRequest->receipt_number]));

        // Then: The request should succeed
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'message' => __('messages.common.deleted', ['item' => __('messages.entities.repair_request.singular')])
        ]);

        // Verify the repair request is soft deleted
        $this->assertSoftDeleted('repair_requests', ['id' => $repairRequest->id]);

        // Verify the images are deleted from storage
        Storage::assertMissing('repair_requests/test1.jpg');
        Storage::assertMissing('repair_requests/test2.jpg');
    }

    /**
     * Test that deleting an already deleted repair request returns 404.
     * This ensures proper handling of soft-deleted repair requests.
     */
    public function test_deleting_already_deleted_repair_request_returns_404(): void
    {
        // Given: A repair request that has been soft deleted
        $repairRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::PENDING->value,
        ]);
        $repairRequest->delete(); // Soft delete the repair request

        $this->assertSoftDeleted('repair_requests', ['id' => $repairRequest->id]);

        // When: An admin user attempts to delete the already deleted repair request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'DELETE', route('repair-request.destroy', ['repairRequest' => $repairRequest->receipt_number]));

        // Then: The request should fail with a 404 Not Found status
        $response->assertStatus(404);
    }

    /**
     * Test that successful deletion returns the correct message.
     * This ensures that the API returns the expected success message.
     */
    public function test_successful_deletion_returns_correct_message(): void
    {
        // Given: An existing repair request
        $repairRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::PENDING->value,
        ]);

        // When: An admin user deletes the repair request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'DELETE', route('repair-request.destroy', ['repairRequest' => $repairRequest->receipt_number]));

        // Then: The response should contain the correct success message
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message']);
        $response->assertJsonFragment([
            'status' => 200,
            'message' => __('messages.common.deleted', ['item' => __('messages.entities.repair_request.singular')])
        ]);
    }

    /**
     * Test deletion with different repair request statuses.
     * This ensures that repair requests can be deleted regardless of their status.
     */
    public function test_can_delete_repair_request_with_different_statuses(): void
    {
        Storage::fake('public');
        
        // Test with different repair statuses
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
            // Given: A repair request with the current status
            $repairRequest = RepairRequest::factory()->create([
                'repair_status' => $status->value,
            ]);

            // When: Admin deletes the repair request
            $response = $this->apiAs($admin, 'DELETE', route('repair-request.destroy', ['repairRequest' => $repairRequest->receipt_number]));            // Then: The deletion should succeed regardless of status
            $response->assertStatus(200);
            $this->assertSoftDeleted('repair_requests', ['id' => $repairRequest->id]);
        }
    }

    /**
     * Test that deletion works with repair requests that have no images.
     * This ensures that the deletion process works for repair requests without associated images.
     */
    public function test_can_delete_repair_request_without_images(): void
    {
        // Given: A repair request without images
        $repairRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::PENDING->value,
        ]);
        
        $this->assertEquals(0, $repairRequest->images()->count(), 'Repair request should have no images');

        // When: An admin user deletes the repair request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'DELETE', route('repair-request.destroy', ['repairRequest' => $repairRequest->receipt_number]));

        // Then: The request should succeed
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'message' => __('messages.common.deleted', ['item' => __('messages.entities.repair_request.singular')])
        ]);

        // Verify the repair request is soft deleted
        $this->assertSoftDeleted('repair_requests', ['id' => $repairRequest->id]);
    }
}