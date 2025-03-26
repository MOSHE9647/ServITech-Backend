<?php

namespace Tests\Feature;

use App\Enums\UserRoles;
use App\Models\RepairRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GetRepairRequestsTest extends TestCase
{
    use RefreshDatabase;  // Reset the database after each test

    /**
     * Set up the test environment.
     * This method seeds the database before each test.
     */
    protected function setUp(): void
    {
        parent::setUp(); // Call the parent setUp method
        $this->seed(class: DatabaseSeeder::class); // Seed the database
    }

    public function test_an_authenticated_user_can_get_all_repair_requests()
    {
        // Given: An authenticated admin user and some repair requests in the database
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists
        RepairRequest::factory()->count(5)->create();

        // When: The user requests the list of repair requests
        $response = $this->apiAs($user, 'GET', "{$this->apiBase}/repair-request");
        // dd($response->json());

        // Then: The request should succeed, and the response should contain the repair requests
        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'status', 'message']);
        $response->assertJsonFragment([
            'status' => 200,
            'message'=> __('messages.repair_request.retrieved_list'),
        ]);

        $this->assertCount(5, $response->json('data.repairRequests'));
    }

    public function test_an_authenticated_non_admin_user_can_not_get_repair_requests()
    {
        // Given: An authenticated non-admin user and some repair requests in the database
        $user = User::factory()->create([
            'name'=> 'John Doe',
            'email'=> 'john.doe@example.com',
        ]);
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists
        $user->assignRole(UserRoles::USER);

        RepairRequest::factory()->count(5)->create();

        // When: The user requests the list of repair requests
        $response = $this->apiAs($user, 'GET', "{$this->apiBase}/repair-request");
        // dd($response->json());

        // Then: The request should fail with a 403 Forbidden status
        $response->assertStatus(403);
        $response->assertJsonStructure(['message']);
        $response->assertJsonFragment([
            'status'=> 403,
            'message'=> __('User does not have the right roles.'),
        ]);
    }

    public function test_an_authenticated_user_can_get_a_single_repair_request_by_receipt_number()
    {
        // Given: An authenticated admin user and a repair request in the database
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists
        $repairRequest = RepairRequest::factory()->create();

        // When: The user requests the repair request by its receipt number
        $response = $this->apiAs($user, 'GET', "{$this->apiBase}/repair-request/{$repairRequest->receipt_number}");

        // Then: The request should succeed, and the response should contain the repair request
        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'status', 'message']);
        $response->assertJsonFragment(['receipt_number' => $repairRequest->receipt_number]);
    }

    public function test_an_authenticated_user_can_not_get_a_single_repair_request_by_other_identifier()
    {
        // Given: An authenticated admin user and a repair request in the database
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists
        $repairRequest = RepairRequest::factory()->create();

        // When: The user attempts to request the repair request by an invalid identifier
        $response = $this->apiAs($user, 'GET', "{$this->apiBase}/repair-request/{$repairRequest->id}");

        // Then: The request should fail with a 404 Not Found status
        $response->assertStatus(404);
        $response->assertJsonStructure(['message']);
    }

    public function test_an_authenticated_non_admin_user_can_not_get_a_single_repair_request()
    {
        // Given: An authenticated non-admin user and a repair request in the database
        $user = User::factory()->create([
            'name'=> 'John Doe',
            'email'=> 'john.doe@example.com',
        ]);
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists
        $user->assignRole(UserRoles::USER);

        $repairRequest = RepairRequest::factory()->create();

        // When: A non-authenticated user attempts to request the repair request by its receipt number
        $response = $this->apiAs($user, 'GET', "{$this->apiBase}/repair-request/{$repairRequest->receipt_number}");

        // Then: The request should fail with a 403 Forbidden status
        $response->assertStatus(403);
        $response->assertJsonStructure(['message']);
        $response->assertJsonFragment([
            'status'=> 403,
            'message'=> __('User does not have the right roles.'),
        ]);
    }

    public function test_a_non_authenticated_user_can_not_get_a_single_repair_request()
    {
        // Given: A repair request in the database
        $repairRequest = RepairRequest::factory()->create();

        // When: A non-authenticated user attempts to request the repair request by its receipt number
        $response = $this->getJson("{$this->apiBase}/repair-request/{$repairRequest->receipt_number}");

        // Then: The request should fail with a 401 Unauthorized status
        $response->assertStatus(401);
        $response->assertJsonStructure(['message']);
    }
}