<?php

namespace Tests\Feature\RepairRequestTests;

use App\Enums\UserRoles;
use App\Models\RepairRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetRepairRequestsTest extends TestCase
{
    use RefreshDatabase; // Reset the database after each test

    /**
     * Set up the test environment.
     * This method seeds the database before each test.
     */
    protected function setUp(): void
    {
        parent::setUp(); // Call the parent setUp method
        $this->seed(DatabaseSeeder::class); // Seed the database
    }

    /**
     * Test that an authenticated admin user can get all repair requests.
     * This ensures that the repair requests are retrieved successfully.
     */
    public function test_an_authenticated_user_can_get_all_repair_requests()
    {
        // Given: An authenticated admin user and some repair requests in the database
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists
        RepairRequest::factory()->count(5)->create();

        // When: The user requests the list of repair requests
        $response = $this->apiAs($user, 'GET', route('repair-request.index'));

        // Then: The request should succeed, and the response should contain the repair requests
        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'status', 'message']);
        $response->assertJsonFragment([
            'status' => 200,
            'message' => __('messages.repair_request.retrieved_list'),
        ]);

        $this->assertCount(5, $response->json('data.repairRequests'));
    }

    /**
     * Test that an authenticated non-admin user cannot get repair requests.
     * This ensures that the request fails with a 403 Forbidden status.
     */
    public function test_an_authenticated_non_admin_user_can_not_get_repair_requests()
    {
        // Given: An authenticated non-admin user and some repair requests in the database
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ]);
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists
        $user->assignRole(UserRoles::USER);

        RepairRequest::factory()->count(5)->create();

        // When: The user requests the list of repair requests
        $response = $this->apiAs($user, 'GET', route('repair-request.index'));

        // Then: The request should fail with a 403 Forbidden status
        $response->assertStatus(403);
        $response->assertJsonStructure(['message']);
        $response->assertJsonFragment([
            'status' => 403,
            'message' => __('User does not have the right roles.'),
        ]);
    }

    /**
     * Test that an authenticated admin user can get a single repair request by receipt number.
     * This ensures that the repair request is retrieved successfully.
     */
    public function test_an_authenticated_user_can_get_a_single_repair_request_by_receipt_number()
    {
        // Given: An authenticated admin user and a repair request in the database
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists
        $repairRequest = RepairRequest::factory()->create();

        // When: The user requests the repair request by its receipt number
        $response = $this->apiAs($user, 'GET', route('repair-request.show', ['repairRequest' => $repairRequest->receipt_number]));

        // Then: The request should succeed, and the response should contain the repair request
        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'status', 'message']);
        $response->assertJsonFragment(['receipt_number' => $repairRequest->receipt_number]);
    }

    /**
     * Test that an authenticated admin user cannot get a single repair request by an invalid identifier.
     * This ensures that the request fails with a 404 Not Found status.
     */
    public function test_an_authenticated_user_can_not_get_a_single_repair_request_by_other_identifier()
    {
        // Given: An authenticated admin user and a repair request in the database
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists
        $repairRequest = RepairRequest::factory()->create();

        // When: The user attempts to request the repair request by an invalid identifier
        $response = $this->apiAs($user, 'GET', route('repair-request.show', ['repairRequest' => $repairRequest->id]));

        // Then: The request should fail with a 404 Not Found status
        $response->assertStatus(404);
        $response->assertJsonStructure(['message']);
    }

    /**
     * Test that an authenticated non-admin user cannot get a single repair request.
     * This ensures that the request fails with a 403 Forbidden status.
     */
    public function test_an_authenticated_non_admin_user_can_not_get_a_single_repair_request()
    {
        // Given: An authenticated non-admin user and a repair request in the database
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ]);
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists
        $user->assignRole(UserRoles::USER);

        $repairRequest = RepairRequest::factory()->create();

        // When: The user attempts to request the repair request by its receipt number
        $response = $this->apiAs($user, 'GET', route('repair-request.show', ['repairRequest' => $repairRequest->receipt_number]));

        // Then: The request should fail with a 403 Forbidden status
        $response->assertStatus(403);
        $response->assertJsonStructure(['message']);
        $response->assertJsonFragment([
            'status' => 403,
            'message' => __('User does not have the right roles.'),
        ]);
    }

    /**
     * Test that a non-authenticated user cannot get a single repair request.
     * This ensures that the request fails with a 401 Unauthorized status.
     */
    public function test_a_non_authenticated_user_can_not_get_a_single_repair_request()
    {
        // Given: A repair request in the database
        $repairRequest = RepairRequest::factory()->create();

        // When: A non-authenticated user attempts to request the repair request by its receipt number
        $response = $this->getJson(route('repair-request.show', ['repairRequest' => $repairRequest->receipt_number]));

        // Then: The request should fail with a 401 Unauthorized status
        $response->assertStatus(401);
        $response->assertJsonStructure(['message']);
    }
}