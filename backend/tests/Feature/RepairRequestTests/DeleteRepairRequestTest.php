<?php

namespace Tests\Feature\RepairRequestTests;

use App\Enums\UserRoles;
use App\Models\RepairRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteRepairRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Set up the test environment.
     * This method seeds the database before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(); // Seed the database
    }

    /**
     * Test that an authenticated admin user can delete a repair request by receipt number.
     * This ensures that the repair request is logically deleted from the database.
     */
    public function test_an_authenticated_admin_user_can_delete_a_repair_request_by_receipt_number()
    {
        // Given: An authenticated admin user and a repair request in the database
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists
        $repairRequest = RepairRequest::factory()->create();

        // When: The admin user attempts to delete the repair request by its receipt number
        $response = $this->apiAs($user, 'DELETE', route('repair-request.destroy', ['repairRequest' => $repairRequest->receipt_number]));

        // Then: The request should succeed, and the repair request should be logically deleted from the database
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message']);
        $response->assertJsonFragment([
            'status' => 200,
            'message' => __('messages.repair_request.deleted'),
        ]);

        $this->assertSoftDeleted('repair_requests', ['id' => $repairRequest->id]);
    }

    /**
     * Test that a non-authenticated user cannot delete a repair request.
     * This ensures that the request fails with a 401 Unauthorized status.
     */
    public function test_a_non_authenticated_user_cannot_delete_a_repair_request()
    {
        // Given: A repair request in the database
        $repairRequest = RepairRequest::factory()->create();

        // When: A non-authenticated user attempts to delete the repair request
        $response = $this->deleteJson(route('repair-request.destroy', ['repairRequest' => $repairRequest->receipt_number]));

        // Then: The request should fail with a 401 Unauthorized status
        $response->assertStatus(401);
        $response->assertJsonStructure(['message']);
    }

    /**
     * Test that an authenticated admin user cannot delete a repair request with an invalid identifier.
     * This ensures that the request fails with a 404 Not Found status.
     */
    public function test_an_authenticated_admin_user_cannot_delete_a_repair_request_with_invalid_identifier()
    {
        // Given: An authenticated admin user
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        // When: The admin user attempts to delete the repair request with an invalid identifier
        $response = $this->apiAs($user, 'DELETE', route('repair-request.destroy', ['repairRequest' => 'invalid-identifier']));

        // Then: The request should fail with a 404 Not Found status
        $response->assertStatus(404);
        $response->assertJsonStructure(['message']);
    }
}