<?php

namespace Tests\Feature;

use App\Enums\UserRoles;
use App\Models\RepairRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteRepairRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(); // Seed the database
    }

    public function test_an_authenticated_admin_user_can_delete_a_repair_request_by_receipt_number()
    {
        // Given: An authenticated admin user and a repair request in the database
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists
        $repairRequest = RepairRequest::factory()->create();

        // When: The admin user attempts to delete the repair request by its receipt number
        $response = $this->apiAs($user, 'DELETE', "{$this->apiBase}/repair-request/{$repairRequest->receipt_number}");

        // Then: The request should succeed, and the repair request should be logically deleted from the database
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message']);
        $response->assertJsonFragment([
            'status' => 200,
            'message' => __('messages.repair_request.deleted'),
        ]);

        $this->assertDatabaseHas('repair_requests', ['deleted_at' => now()->format('Y-m-d H:i:s')]);
    }

    public function test_a_non_authenticated_admin_user_cannot_delete_a_repair_request()
    {
        // Given: A repair request in the database
        $repairRequest = RepairRequest::factory()->create();

        // When: A non-authenticated user attempts to delete the repair request
        $response = $this->deleteJson("{$this->apiBase}/repair-request/{$repairRequest->receipt_number}");

        // Then: The request should fail with a 401 Unauthorized status
        $response->assertStatus(401);
        $response->assertJsonStructure(['message']);
    }

    public function test_an_authenticated_admin_user_cannot_delete_a_repair_request_with_invalid_identifier()
    {
        // Given: An authenticated admin user and a repair request in the database
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists
        $repairRequest = RepairRequest::factory()->create();

        // When: The admin user attempts to delete the repair request with an invalid identifier
        $response = $this->apiAs($user, 'DELETE', "{$this->apiBase}/repair-request/invalid-identifier");

        // Then: The request should fail with a 404 Not Found status
        $response->assertStatus(404);
        $response->assertJsonStructure(['message']);
    }
}