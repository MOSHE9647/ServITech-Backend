<?php

namespace Tests\Feature\SupportRequest;

use App\Enums\UserRoles;
use App\Models\SupportRequest;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportRequestIndexTest extends TestCase
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
     * Test that an authenticated user can retrieve their own support requests.
     * This ensures that users can only see their own support requests.
     */
    public function test_an_authenticated_user_can_retrieve_their_own_support_requests(): void
    {
        // Given: An authenticated user with multiple support requests
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        // Create support requests for this user
        $supportRequest1 = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'date' => now()->subDays(2)->format('Y-m-d'),
            'location' => 'Location 1',
            'detail' => 'Detail 1',
        ]);

        $supportRequest2 = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'date' => now()->subDays(1)->format('Y-m-d'),
            'location' => 'Location 2',
            'detail' => 'Detail 2',
        ]);

        $supportRequest3 = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
            'location' => 'Location 3',
            'detail' => 'Detail 3',
        ]);

        // When: The user requests their support requests
        $response = $this->apiAs($user, 'GET', route('support-request.index'));

        // Then: The response should succeed and contain only their support requests
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'supportRequests' => [
                    '*' => [
                        'id',
                        'user_id',
                        'date',
                        'location',
                        'detail',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]
        ]);

        $response->assertJsonFragment([
            'message' => __('messages.common.retrieved_all', ['items' => __('messages.entities.support_request.plural')])
        ]);

        // Verify all 3 support requests are returned
        $responseData = $response->json();
        $this->assertCount(3, $responseData['data']['supportRequests']);

        // Verify all returned support requests belong to the authenticated user
        foreach ($responseData['data']['supportRequests'] as $supportRequest) {
            $this->assertEquals($user->id, $supportRequest['user_id']);
        }
    }    /**
     * Test that an authenticated admin user can retrieve their own support requests.
     * This ensures that admin users also only see their own support requests.
     */
    public function test_an_authenticated_admin_user_can_retrieve_their_own_support_requests(): void
    {
        // Given: An authenticated admin user with support requests
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        // Get initial count to account for any existing data
        $initialCount = SupportRequest::where('user_id', $admin->id)->count();

        // Create support requests for this admin
        $adminSupportRequest = SupportRequest::factory()->create([
            'user_id' => $admin->id,
            'date' => now()->format('Y-m-d'),
            'location' => 'Admin Office',
            'detail' => 'Admin needs help with system',
        ]);

        // When: The admin requests their support requests
        $response = $this->apiAs($admin, 'GET', route('support-request.index'));

        // Then: The response should succeed and contain their support requests
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message', 'data' => ['supportRequests']]);

        $responseData = $response->json();
        $this->assertCount($initialCount + 1, $responseData['data']['supportRequests']);
        
        // Verify all returned requests belong to this admin
        foreach ($responseData['data']['supportRequests'] as $supportRequest) {
            $this->assertEquals($admin->id, $supportRequest['user_id']);
        }
    }

    /**
     * Test that a non-authenticated user cannot retrieve support requests.
     * This ensures that authentication is required to access support requests.
     */
    public function test_a_non_authenticated_user_cannot_retrieve_support_requests(): void
    {
        // Given: Some support requests exist in the database
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        SupportRequest::factory()->create([
            'user_id' => $user->id,
        ]);

        // When: A non-authenticated user attempts to retrieve support requests
        $response = $this->getJson(route('support-request.index'));

        // Then: The request should fail with a 401 Unauthorized status
        $response->assertStatus(401);
        $response->assertJsonStructure(['status', 'message']);
    }

    /**
     * Test that support requests are returned in descending order by ID.
     * This ensures that the most recent support requests appear first.
     */
    public function test_support_requests_are_returned_in_descending_order_by_id(): void
    {
        // Given: An authenticated user with multiple support requests created at different times
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        // Create support requests in a specific order
        $firstRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'location' => 'First Location',
            'detail' => 'First detail',
        ]);

        $secondRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'location' => 'Second Location',
            'detail' => 'Second detail',
        ]);

        $thirdRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'location' => 'Third Location',
            'detail' => 'Third detail',
        ]);

        // When: The user requests their support requests
        $response = $this->apiAs($user, 'GET', route('support-request.index'));

        // Then: The support requests should be returned in descending order by ID
        $response->assertStatus(200);
        $responseData = $response->json();
        $supportRequests = $responseData['data']['supportRequests'];

        $this->assertCount(3, $supportRequests);

        // Verify the order (most recent first)
        $this->assertEquals($thirdRequest->id, $supportRequests[0]['id']);
        $this->assertEquals($secondRequest->id, $supportRequests[1]['id']);
        $this->assertEquals($firstRequest->id, $supportRequests[2]['id']);
    }

    /**
     * Test that returns empty array when user has no support requests.
     * This ensures the endpoint handles users with no support requests gracefully.
     */
    public function test_returns_empty_array_when_user_has_no_support_requests(): void
    {
        // Given: An authenticated user with no support requests
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        // Ensure this user has no support requests
        SupportRequest::where('user_id', $user->id)->delete();

        // When: The user requests their support requests
        $response = $this->apiAs($user, 'GET', route('support-request.index'));

        // Then: The response should succeed with an empty array
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message', 'data' => ['supportRequests']]);

        $responseData = $response->json();
        $this->assertCount(0, $responseData['data']['supportRequests']);
        $this->assertIsArray($responseData['data']['supportRequests']);
    }

    /**
     * Test that soft deleted support requests are not included in the response.
     * This ensures that deleted support requests are properly filtered out.
     */
    public function test_soft_deleted_support_requests_are_not_included(): void
    {
        // Given: An authenticated user with both active and soft deleted support requests
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        // Create active support request
        $activeSupportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'location' => 'Active Location',
            'detail' => 'Active detail',
        ]);

        // Create soft deleted support request
        $deletedSupportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'location' => 'Deleted Location',
            'detail' => 'Deleted detail',
        ]);
        $deletedSupportRequest->delete(); // Soft delete

        // When: The user requests their support requests
        $response = $this->apiAs($user, 'GET', route('support-request.index'));

        // Then: Only the active support request should be returned
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertCount(1, $responseData['data']['supportRequests']);
        $this->assertEquals($activeSupportRequest->id, $responseData['data']['supportRequests'][0]['id']);
        $this->assertEquals('Active Location', $responseData['data']['supportRequests'][0]['location']);
    }

    /**
     * Test that the response contains the correct success message.
     * This ensures that the API returns properly localized messages.
     */
    public function test_response_contains_correct_success_message(): void
    {
        // Given: An authenticated user with a support request
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        SupportRequest::factory()->create([
            'user_id' => $user->id,
        ]);

        // When: The user requests their support requests
        $response = $this->apiAs($user, 'GET', route('support-request.index'));

        // Then: The response should contain the correct message
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => 200,
            'message' => __('messages.common.retrieved_all', ['items' => __('messages.entities.support_request.plural')])
        ]);
    }

    /**
     * Test that all required fields are present in the response.
     * This ensures the API returns complete support request data.
     */
    public function test_all_required_fields_are_present_in_response(): void
    {
        // Given: An authenticated user with a support request
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
            'location' => 'Test Location',
            'detail' => 'Test detail',
        ]);

        // When: The user requests their support requests
        $response = $this->apiAs($user, 'GET', route('support-request.index'));

        // Then: All required fields should be present
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'supportRequests' => [
                    '*' => [
                        'id',
                        'user_id',
                        'date',
                        'location', 
                        'detail',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]
        ]);

        $responseData = $response->json();
        $returnedSupportRequest = $responseData['data']['supportRequests'][0];

        // Verify the data matches what was created
        $this->assertEquals($supportRequest->id, $returnedSupportRequest['id']);
        $this->assertEquals($user->id, $returnedSupportRequest['user_id']);
        $this->assertEquals('Test Location', $returnedSupportRequest['location']);
        $this->assertEquals('Test detail', $returnedSupportRequest['detail']);
        $this->assertNotNull($returnedSupportRequest['date']);
        $this->assertNotNull($returnedSupportRequest['created_at']);
        $this->assertNotNull($returnedSupportRequest['updated_at']);
    }

    /**
     * Test that the endpoint handles large numbers of support requests efficiently.
     * This ensures the API can handle users with many support requests.
     */
    public function test_handles_large_number_of_support_requests(): void
    {
        // Given: An authenticated user with many support requests
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        // Create 50 support requests
        $supportRequests = SupportRequest::factory()->count(50)->create([
            'user_id' => $user->id,
        ]);

        // When: The user requests their support requests
        $response = $this->apiAs($user, 'GET', route('support-request.index'));

        // Then: All support requests should be returned successfully
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertCount(50, $responseData['data']['supportRequests']);

        // Verify they are all for the correct user
        foreach ($responseData['data']['supportRequests'] as $supportRequest) {
            $this->assertEquals($user->id, $supportRequest['user_id']);
        }
    }
}